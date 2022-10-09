<?php

namespace Kaliop\eZWorkflowEngineBundle\Core\StorageHandler\Database;

use Kaliop\eZMigrationBundle\Core\StorageHandler\Database\Migration as StorageMigration;
use Kaliop\eZMigrationBundle\API\Value\Migration;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Kaliop\eZMigrationBundle\API\ConfigResolverInterface;
use Kaliop\eZWorkflowEngineBundle\API\Value\Workflow as APIWorkflow;
use eZ\Publish\Core\Persistence\Database\DatabaseHandler;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Schema;

/**
 * @todo add methods aliases using 'Workflow' in place of 'Migration'
 */
class Workflow extends StorageMigration
{
    protected $fieldList = 'migration, md5, path, execution_date, status, execution_error, signal_name';

    /**
     * @param DatabaseHandler $dbHandler
     * @param string $tableNameParameter
     * @param ConfigResolverInterface $configResolver
     * @throws \Exception
     */
    public function __construct(DatabaseHandler $dbHandler, $tableNameParameter = 'kaliop_workflows', ConfigResolverInterface $configResolver = null)
    {
        parent::__construct($dbHandler, $tableNameParameter, $configResolver);
    }

    /**
     * @param MigrationDefinition $migrationDefinition
     * @return APIWorkflow
     * @throws \Exception
     */
    public function addMigration(MigrationDefinition $migrationDefinition)
    {
        throw new \Exception("Can not add workflows to the database if not when starting them");
    }

    protected function createMigration(MigrationDefinition $migrationDefinition, $status, $action, $force = false)
    {
        $this->createTableIfNeeded();

        $workflowName = $this->getWorkflowName($migrationDefinition->name);

        // select for update

        // annoyingly enough, neither Doctrine nor EZP provide built in support for 'FOR UPDATE' in their query builders...
        // at least the doctrine one allows us to still use parameter binding when we add our sql particle
        $conn = $this->dbHandler->getConnection();

        $qb = $conn->createQueryBuilder();
        $qb->select('*')
            ->from($this->tableName, 'm')
            ->where('migration = ?');
        $sql = $qb->getSQL() . ' FOR UPDATE';

        $conn->beginTransaction();

        $stmt = $conn->executeQuery($sql, array($workflowName));
        $existingMigrationData = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (is_array($existingMigrationData)) {
            // workflow exists - start it

            $workflowName = $existingMigrationData['name'];

            // fail if it was already executing or already done
            if ($existingMigrationData['status'] == Migration::STATUS_STARTED) {
                // commit to release the lock
                $conn->commit();
                throw new \Exception("Workflow '{$migrationDefinition->name}' can not be $action as it is already executing");
            }
            if ($existingMigrationData['status'] == Migration::STATUS_DONE) {
                // commit to release the lock
                $conn->commit();
                throw new \Exception("Workflow '{$migrationDefinition->name}' can not be $action as it was already executed");
            }
            if ($existingMigrationData['status'] == Migration::STATUS_SKIPPED) {
                // commit to release the lock
                $conn->commit();
                throw new \Exception("Workflow '{$migrationDefinition->name}' can not be $action as it was already skipped");
            }

            // do not set migration start date if we are skipping it
            $migration = new APIWorkflow(
                $workflowName,
                md5($migrationDefinition->rawDefinition),
                $migrationDefinition->path,
                ($status == Migration::STATUS_SKIPPED ? null : time()),
                $status,
                null,
                $migrationDefinition->signalName
            );
            $conn->update(
                $this->tableName,
                array(
                    'execution_date' => $migration->executionDate,
                    'status' => $status,
                    'execution_error' => null
                ),
                array('migration' => $workflowName)
            );
            $conn->commit();

        } else {
            // migration did not exist. Create it!

            // commit immediately, to release the lock and avoid deadlocks
            $conn->commit();

            $migration = new APIWorkflow(
                $workflowName,
                md5($migrationDefinition->rawDefinition),
                $migrationDefinition->path,
                ($status == Migration::STATUS_SKIPPED ? null : time()),
                $status,
                null,
                $migrationDefinition->signalName
            );
            $conn->insert($this->tableName, $this->migrationToArray($migration));
        }

        return $migration;
    }

    public function skipMigration(MigrationDefinition $migrationDefinition)
    {
        throw new \Exception("Can not tag workflows in the database as to be skipped");
    }

    public function createTable()
    {
        /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager $sm */
        $sm = $this->dbHandler->getConnection()->getSchemaManager();
        $dbPlatform = $sm->getDatabasePlatform();

        $schema = new Schema();

        $t = $schema->createTable($this->tableName);
        $t->addColumn('migration', 'string', array('length' => 255));
        $t->addColumn('path', 'string', array('length' => 4000));
        $t->addColumn('md5', 'string', array('length' => 32));
        $t->addColumn('execution_date', 'integer', array('notnull' => false));
        $t->addColumn('status', 'integer', array('default ' => Migration::STATUS_TODO));
        $t->addColumn('execution_error', 'string', array('length' => 4000, 'notnull' => false));
        $t->addColumn('signal_name', 'string', array('length' => 4000, 'notnull' => false));
        $t->setPrimaryKey(array('migration'));
        // in case users want to look up migrations by their full path
        // NB: disabled for the moment, as it causes problems on some versions of mysql which limit index length to 767 bytes,
        // and 767 bytes can be either 255 chars or 191 chars depending on charset utf8 or utf8mb4...
        //$t->addIndex(array('path'));

        /// @todo add support for utf8mb4 charset

        foreach ($schema->toSql($dbPlatform) as $sql) {
            $this->dbHandler->exec($sql);
        }
    }

    protected function migrationToArray(Migration $migration)
    {
        return array(
            'migration' => $migration->name,
            'md5' => $migration->md5,
            'path' => $migration->path,
            'execution_date' => $migration->executionDate,
            'status' => $migration->status,
            'execution_error' => $migration->executionError,
            'signal_name' => $migration->signalName
        );
    }

    protected function arrayToMigration(array $data)
    {
        return new APIWorkflow(
            $data['migration'],
            $data['md5'],
            $data['path'],
            $data['execution_date'],
            $data['status'],
            $data['execution_error'],
            $data['signal_name']
        );
    }

    /**
     * @param string $workflowDefinitionName
     * @return string currently YYYYMMDDHHmmSSuuu_PID/$workflowDefinitionName
     * @todo for strict sorting across leap hours we should use unix timestamp
     * @bug if we have a cluster of servers, we migth hit a pid collision...
     */
    protected function getWorkflowName($workflowDefinitionName)
    {
        $mtime = explode(' ', microtime());
        $time = date('YmdHis', $mtime[1]). substr($mtime[0], 2, 3);
        return $time . '/' . getmypid() . '/' . $workflowDefinitionName;
    }
}
