<?php

namespace Kaliop\eZWorkflowEngineBundle\Core;

use Kaliop\eZMigrationBundle\Core\MigrationService;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Kaliop\eZMigrationBundle\API\Collection\MigrationDefinitionCollection;

/**
 * @todo add phpdoc to help IDEs
 */
class WorkflowServiceFacade
{
    protected $innerService;

    public function __construct(MigrationService $innerService)
    {
        $this->innerService = $innerService;
    }

    /**
     * Unlike its parent's similar function, this one only deals with *parsed* definitions
     * @param string[] $paths
     * @return MigrationDefinitionCollection
     * @todo add caching as this is quite inefficient
     */
    public function getWorkflowsDefinitions($paths = array())
    {
        $defs = array();

        foreach($this->innerService->getMigrationsDefinitions() as $key => $definition) {
            if ($definition->status == MigrationDefinition::STATUS_TO_PARSE) {
                $definition = $this->innerService->parseMigrationDefinition($definition);
            }
            $defs[$key] = $definition;
        }

        return new MigrationDefinitionCollection($defs);
    }

    /**
     * Returns VALID definitions for a given signal
     * @param $signalName
     * @param string[] $paths
     * @return MigrationDefinitionCollection
     * @todo add caching as this is quite inefficient
     */
    public function getValidWorkflowsDefinitionsForSignal($signalName, $paths = array())
    {
        $defs = array();

        foreach($this->getWorkflowsDefinitions($paths) as $key => $definition) {
            /// @todo add safety check that we got back in fact a WorkflowDefinition
            if ($definition->signalName === $signalName && $definition->status == MigrationDefinition::STATUS_PARSED) {
                $defs[$key] = $definition;
            }
        }

        return new MigrationDefinitionCollection($defs);
    }

    public function __call($name, array $arguments)
    {
        $name = str_replace('Workflow', 'Migration', $name);
        return call_user_func_array(array($this->innerService, $name), $arguments);
    }
}
