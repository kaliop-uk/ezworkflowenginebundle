<?php

namespace Kaliop\eZWorkflowEngineBundle\Core\StorageHandler\Database;

use Kaliop\eZMigrationBundle\Core\StorageHandler\Database\Migration as StorageMigration;
use Kaliop\eZMigrationBundle\API\Value\Migration;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Kaliop\eZWorkflowEngineBundle\API\Value\Workflow as APIWorkflow;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Schema;

class Workflow extends StorageMigration
{
    protected $fieldList = 'migration, md5, path, execution_date, status, execution_error, slot_name';

    public function addMigration(MigrationDefinition $migrationDefinition)
    {
        $this->createTableIfNeeded();

        $conn = $this->dbHandler->getConnection();

        $migration = new APIWorkflow(
            $migrationDefinition->name,
            md5($migrationDefinition->rawDefinition),
            $migrationDefinition->path,
            null,
            Migration::STATUS_TODO,
            $migrationDefinition->slotName
        );
        try {
            $conn->insert($this->tableName, $this->migrationToArray($migration));
        } catch (UniqueConstraintViolationException $e) {
            throw new \Exception("Migration '{$migrationDefinition->name}' already exists");
        }

        return $migration;
    }

    protected function createMigration(MigrationDefinition $migrationDefinition, $status, $action)
    {
        $this->createTableIfNeeded();

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

        $stmt = $conn->executeQuery($sql, array($migrationDefinition->name));
        $existingMigrationData = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (is_array($existingMigrationData)) {
            // migration exists

            // fail if it was already executing or already done
            if ($existingMigrationData['status'] == Migration::STATUS_STARTED) {
                // commit to release the lock
                $conn->commit();
                throw new \Exception("Migration '{$migrationDefinition->name}' can not be $action as it is already executing");
            }
            if ($existingMigrationData['status'] == Migration::STATUS_DONE) {
                // commit to release the lock
                $conn->commit();
                throw new \Exception("Migration '{$migrationDefinition->name}' can not be $action as it was already executed");
            }
            if ($existingMigrationData['status'] == Migration::STATUS_SKIPPED) {
                // commit to release the lock
                $conn->commit();
                throw new \Exception("Migration '{$migrationDefinition->name}' can not be $action as it was already skipped");
            }

            // do not set migration start date if we are skipping it
            $migration = new Migration(
                $migrationDefinition->name,
                md5($migrationDefinition->rawDefinition),
                $migrationDefinition->path,
                ($status == Migration::STATUS_SKIPPED ? null : time()),
                $status
            );
            $conn->update(
                $this->tableName,
                array(
                    'execution_date' => $migration->executionDate,
                    'status' => $status,
                    'execution_error' => null
                ),
                array('migration' => $migrationDefinition->name)
            );
            $conn->commit();

        } else {
            // migration did not exist. Create it!

            // commit immediately, to release the lock and avoid deadlocks
            $conn->commit();

            $migration = new APIWorkflow(
                $migrationDefinition->name,
                md5($migrationDefinition->rawDefinition),
                $migrationDefinition->path,
                ($status == Migration::STATUS_SKIPPED ? null : time()),
                $status,
                $migrationDefinition->slotName
            );
            $conn->insert($this->tableName, $this->migrationToArray($migration));
        }

        return $migration;
    }

    public function createMigrationsTable()
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
        $t->addColumn('slot_name', 'string', array('length' => 4000, 'notnull' => false));
        $t->setPrimaryKey(array('migration'));
        // in case users want to look up migrations by their full path
        // NB: disabled for the moment, as it causes problems on some versions of mysql which limit index length to 767 bytes,
        // and 767 bytes can be either 255 chars or 191 chars depending on charset utf8 or utf8mb4...
        //$t->addIndex(array('path'));

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
            'slot_name' => $migration->slotName
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
            $data['slot_name']
        );
    }
}
