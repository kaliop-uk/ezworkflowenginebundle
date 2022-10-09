<?php

namespace Kaliop\eZWorkflowEngineBundle\Core\DefinitionParser;

use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Kaliop\eZMigrationBundle\API\DefinitionParserInterface;
use Kaliop\eZWorkflowEngineBundle\API\Value\WorkflowDefinition;

class JsonDefinitionParser extends AbstractDefinitionParser implements DefinitionParserInterface
{
    public function supports($migrationName)
    {
        $ext = pathinfo($migrationName, PATHINFO_EXTENSION);
        return  $ext == 'json';
    }

    /**
     * Parses a workflow definition file, and returns the list of actions to take
     *
     * @param MigrationDefinition $definition
     * @return WorkflowDefinition
     */
    public function parseMigrationDefinition(MigrationDefinition $definition)
    {
        try {
            $data = json_decode($definition->rawDefinition, true);
        } catch (\Exception $e) {
            return new WorkflowDefinition(
                $definition->name,
                $definition->path,
                $definition->rawDefinition,
                MigrationDefinition::STATUS_INVALID,
                array(),
                $e->getMessage()
            );
        }

        return $this->parseMigrationDefinitionData($data, $definition, 'Json');
    }
}