<?php

namespace Kaliop\eZWorkflowEngineBundle\Core;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Kaliop\eZMigrationBundle\Core\MigrationService;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Kaliop\eZMigrationBundle\API\Collection\MigrationDefinitionCollection;

/**
 * @todo add phpdoc to help IDEs
 */
class WorkflowServiceFacade
{
    protected $innerService;
    protected $cacheDir;
    protected $debugMode;

    public function __construct(MigrationService $innerService, $cacheDir, $debugMode = false)
    {
        $this->innerService = $innerService;
        $this->cacheDir = $cacheDir;
        $this->debugMode = $debugMode;
    }

    /**
     * Unlike its parent's similar function, this one only deals with *parsed* definitions.
     * NB: this function, unlike getValidWorkflowsDefinitionsForSignal, does not cache its results, which might lead to
     * some hard-to troubleshoot weirdness...
     * @param string[] $paths
     * @return MigrationDefinitionCollection
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
     * Returns VALID definitions for a given signal.
     * Uses the Sf cache to speed up the process (recipe taken from http://symfony.com/doc/2.7/components/config/caching.html)
     * @param $signalName
     * @param string[] $paths
     * @return MigrationDefinitionCollection
     */
    public function getValidWorkflowsDefinitionsForSignal($signalName, $paths = array())
    {
        $cacheFile = $this->cacheDir . '/' . md5($signalName) . '/' . md5(serialize($paths)) . '.php';

        $cache = new ConfigCache($cacheFile, $this->debugMode);
        if ($cache->isFresh()) {
            return require $cacheFile;
        }

        $defs = array();
        $resources = array();

        foreach($this->getWorkflowsDefinitions($paths) as $key => $definition) {
            /// @todo add safety check that we got back in fact a WorkflowDefinition
            if ($definition->signalName === $signalName && $definition->status == MigrationDefinition::STATUS_PARSED) {
                $defs[$key] = $definition;
                $resources[] = new FileResource($definition->path);
            }
        }

        $collection = new MigrationDefinitionCollection($defs);

        $code = '<?php return '.var_export($collection, true).';';
        $cache->write($code, $resources);

        return $collection;
    }

    public function __call($name, array $arguments)
    {
        $name = str_replace('Workflow', 'Migration', $name);
        return call_user_func_array(array($this->innerService, $name), $arguments);
    }
}
