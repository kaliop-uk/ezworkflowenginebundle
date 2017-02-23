<?php

namespace Kaliop\eZWorkflowEngineBundle\Core;

use Kaliop\eZMigrationBundle\Core\MigrationService;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Kaliop\eZWorkflowEngineBundle\API\Value\WorkflowDefinition;

class WorkflowServiceInner extends MigrationService
{
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
                            "Can not handle migration step of type '{$step->type}'",
                            $migrationDefinition->slotName
                        );
                    }
                }

                return $migrationDefinition;
            }
        }

        throw new \Exception("No parser available to parse migration definition '{$migrationDefinition->name}'");
    }
}
