<?php

namespace Kaliop\eZWorkflowEngineBundle\Core;

use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Kaliop\eZMigrationBundle\API\Value\Migration;
use Kaliop\eZMigrationBundle\Core\MigrationService;
use Kaliop\eZMigrationBundle\Core\ReferenceResolver\PrefixBasedResolverInterface;
use Kaliop\eZWorkflowEngineBundle\API\Value\WorkflowDefinition;

class WorkflowServiceInner extends MigrationService implements PrefixBasedResolverInterface
{
    protected $eventPrefix = 'ez_workflow.';
    protected $eventEntity = 'workflow';
    protected $referenceRegexp = '/^workflow:/';
    protected $currentWorkflowName;

    public function addMigration(MigrationDefinition $migrationDefinition)
    {
        throw new \Exception("Unsupported operation: direct adding of workflows");
    }

    public function skipMigration(MigrationDefinition $migrationDefinition)
    {
        throw new \Exception("Unsupported operation: tagging of workflows as skipped");
    }

    /**
     * Reimplemented to make sure we always return a WorkflowDefinition
     * @param MigrationDefinition $migrationDefinition this should be a WorkflowDefinition really
     * @return WorkflowDefinition
     * @throws \Exception
     */
    public function parseMigrationDefinition(MigrationDefinition $migrationDefinition)
    {
        foreach ($this->DefinitionParsers as $definitionParser) {
            if ($definitionParser->supports($migrationDefinition->name)) {
                // parse the source file
                $migrationDefinition = $definitionParser->parseMigrationDefinition($migrationDefinition);

                // and make sure we know how to handle all steps
                foreach ($migrationDefinition->steps as $step) {
                    if (!isset($this->executors[$step->type])) {
                        return new WorkflowDefinition(
                            $migrationDefinition->name,
                            $migrationDefinition->path,
                            $migrationDefinition->rawDefinition,
                            MigrationDefinition::STATUS_INVALID,
                            array(),
                            "Can not handle workflow step of type '{$step->type}'",
                            isset($migrationDefinition->signalName) ? $migrationDefinition->signalName : null,
                            isset($migrationDefinition->runAs) ? $migrationDefinition->runAs : false,
                            isset($migrationDefinition->useTransaction) ? $migrationDefinition->useTransaction : false,
                            isset($migrationDefinition->avoidRecursion) ? $migrationDefinition->avoidRecursion : false
                        );
                    }
                }

                return $migrationDefinition;
            }
        }

        throw new \Exception("No parser available to parse workflow definition '{$migrationDefinition->name}'");
    }

    /**
     * Reimplemented to add more parameters to be stored in the context
     *
     * @param MigrationDefinition $migrationDefinition
     * @param bool $useTransaction when set to false, no repo transaction will be used to wrap the migration
     * @param string $defaultLanguageCode
     * @param string|int|false|null $adminLogin when false, current user is used; when null, hardcoded admin account
     * @param $workflowParameters
     * @throws \Exception
     *
     * @todo treating a null and false $adminLogin values differently is prone to hard-to-track errors.
     *       Shall we use instead -1 to indicate the desire to not-login-as-admin-user-at-all ?
     */
    public function executeMigration(MigrationDefinition $migrationDefinition, $useTransaction = true,
        $defaultLanguageCode = null, $adminLogin = null, $workflowParameters = null)
    {
        if ($migrationDefinition->status == MigrationDefinition::STATUS_TO_PARSE) {
            $migrationDefinition = $this->parseMigrationDefinition($migrationDefinition);
        }

        if ($migrationDefinition->status == MigrationDefinition::STATUS_INVALID) {
            /// @todo !important name of entity should be gotten dynamically (migration vs. workflow)
            throw new \Exception("Can not execute migration '{$migrationDefinition->name}': {$migrationDefinition->parsingError}");
        }

        /// @todo add support for setting in $migrationContext a userContentType ?
        $migrationContext = $this->migrationContextFromParameters($defaultLanguageCode, $adminLogin, $workflowParameters);

        // set migration as begun - has to be in own db transaction
        $migration = $this->storageHandler->startMigration($migrationDefinition);

        $this->executeMigrationInner($migration, $migrationDefinition, $migrationContext, 0, $useTransaction, $adminLogin);
    }

    /**
     * Reimplemented to store the name of the current executing workflow
     * @param Migration $migration
     * @param MigrationDefinition $migrationDefinition
     * @param array $migrationContext
     * @param int $stepOffset
     * @param bool $useTransaction when set to false, no repo transaction will be used to wrap the migration
     * @param string|int|false|null $adminLogin used only for committing db transaction if needed. If false or null, hardcoded admin is used
     * @throws \Exception
     */
    protected function executeMigrationInner(Migration $migration, MigrationDefinition $migrationDefinition,
        $migrationContext, $stepOffset = 0, $useTransaction = true, $adminLogin = null)
    {
        $previousWorkflowName = $this->currentWorkflowName;
        $this->currentWorkflowName = $migration->name;
        try {
            parent::executeMigrationInner($migration, $migrationDefinition, $migrationContext, $stepOffset,
                $useTransaction, $adminLogin);
            $this->currentWorkflowName = $previousWorkflowName;
        } catch (\Exception $e) {
            $this->currentWorkflowName = $previousWorkflowName;
            throw $e;
        }
    }

    /**
     * Reimplemented to store in the context the current user at start of workflow
     *
     * @param null $defaultLanguageCode
     * @param null $adminLogin
     * @param array $workflowParameters
     * @return array
     */
    protected function migrationContextFromParameters($defaultLanguageCode = null, $adminLogin = null, $workflowParameters = null)
    {
        $properties = parent::migrationContextFromParameters($defaultLanguageCode, $adminLogin);

        if (!is_array($workflowParameters)) {
            /// @todo log warning
            $workflowParameters = array();
        }

        $workflowParameters = array_merge(
            array(
                'original_user' => $this->repository->getCurrentUser()->login,
                'start_time' => time()
            ),
            $workflowParameters
        );

        $properties['workflow'] = $workflowParameters;

        return $properties;
    }

    /**
     * When restoring the context of the original workflow, we will be running as anon, whereas the original
     * workflow might have been started either as an admin or as then-current user. We need special handling for the
     * latter case
     *
     * @param string $migrationName
     * @param array $context
     */
    public function restoreContext($migrationName, array $context)
    {
        if (array_key_exists('adminUserLogin', $context['context']) && $context['context']['adminUserLogin'] === false) {
            if (isset($context['context']['workflow']['original_user'])) {
                $context['context']['adminUserLogin'] = $context['context']['workflow']['original_user'];
            }
        }

        parent::restoreContext($migrationName, $context);
    }

    ### reference resolver interface

    public function getRegexp()
    {
        return $this->referenceRegexp;
    }

    public function isReference($stringIdentifier)
    {
        if (!is_string($stringIdentifier)) {
            return false;
        }

        return (bool)preg_match($this->referenceRegexp, $stringIdentifier);
    }

    /**
     * @param string $stringIdentifier
     * @return mixed
     * @throws \Exception if the given Identifier is not a reference
     */
    public function getReferenceValue($stringIdentifier)
    {
        $context = $this->getCurrentContext($this->currentWorkflowName);
        if (!is_array($context) || !isset($context['context']['workflow']) || !is_array($context['context']['workflow'])) {
            throw new \Exception('Can not resolve reference to a workflow parameter as workflow context is missing');
        }

        $identifier = preg_replace($this->referenceRegexp, '', $stringIdentifier);

        if (!array_key_exists($identifier, $context['context']['workflow'])) {
            throw new \Exception("No workflow reference set with identifier '$identifier'");
        }

        return $context['context']['workflow'][$identifier];
    }

    /**
     * @param string $stringIdentifier
     * @return mixed $stringIdentifier if not a reference, otherwise the reference vale
     * @throws \Exception if the given Identifier is not a reference
     */
    public function resolveReference($stringIdentifier)
    {
        if ($this->isReference($stringIdentifier)) {
            return $this->getReferenceValue($stringIdentifier);
        }
        return $stringIdentifier;
    }
}
