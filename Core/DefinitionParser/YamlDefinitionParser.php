<?php

namespace Kaliop\eZWorkflowEngineBundle\Core\DefinitionParser;

use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Kaliop\eZMigrationBundle\API\DefinitionParserInterface;
use Kaliop\eZWorkflowEngineBundle\API\Value\WorkflowDefinition;
use Symfony\Component\Yaml\Yaml;

class YamlDefinitionParser extends AbstractDefinitionParser implements DefinitionParserInterface
{
    /**
     * Tells whether the given file can be handled by this handler, by checking e.g. the suffix
     *
     * @param string $migrationName typically a filename
     * @return bool
     */
    public function supports($migrationName)
    {
        $ext = pathinfo($migrationName, PATHINFO_EXTENSION);
        return  $ext == 'yml' || $ext == 'yaml';
    }

    /**
     * Parses a migration definition file, and returns the list of actions to take
     *
     * @param MigrationDefinition $definition
     * @return WorkflowDefinition
     */
    public function parseMigrationDefinition(MigrationDefinition $definition)
    {
        try {
            $data = Yaml::parse($definition->rawDefinition);
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

        return $this->parseMigrationDefinitionData($data, $definition);
    }
}