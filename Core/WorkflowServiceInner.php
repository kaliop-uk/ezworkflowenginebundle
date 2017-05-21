<?php

namespace Kaliop\eZWorkflowEngineBundle\Core;

use Kaliop\eZMigrationBundle\Core\MigrationService;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Kaliop\eZWorkflowEngineBundle\API\Value\WorkflowDefinition;

class WorkflowServiceInner extends MigrationService
{

    protected $eventPrefix = 'ez_workflow.';

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
}
