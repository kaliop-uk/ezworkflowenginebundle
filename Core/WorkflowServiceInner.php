<?php

namespace Kaliop\eZWorkflowEngineBundle\Core;

use Kaliop\eZMigrationBundle\Core\MigrationService;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;

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
}
