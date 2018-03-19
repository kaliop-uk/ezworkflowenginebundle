<?php

namespace Kaliop\eZWorkflowEngineBundle\Core\Loader;

use Kaliop\eZMigrationBundle\Core\Loader\Filesystem as BaseLoader;
use Kaliop\eZWorkflowEngineBundle\API\Value\WorkflowDefinition;
use Symfony\Component\HttpKernel\KernelInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

class Filesystem extends BaseLoader
{

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    #public function __construct(KernelInterface $kernel, $versionDirectory = 'Workflows')
    public function __construct(KernelInterface $kernel, ConfigResolverInterface $configResolver)
    {
        $this->kernel = $kernel;
        #$this->versionDirectory = $versionDirectory;
        $this->configResolver = $configResolver;
    }

    /**
     * @param array $paths either dir names or file names
     * @param bool $returnFilename return either the
     * @return WorkflowDefinition[]|string[] migrations definitions. key: name, value: contents of the definition, as string or file path
     * @throws \Exception
     */
    protected function getDefinitions(array $paths = array(), $returnFilename = false)
    {
        $versionDirectory  = $this->configResolver->getParameter('workflow_directory','ez_workflowengine_bundle');
        // if no paths defined, we look in all bundles
        if (empty($paths)) {
            $paths = array();
            /** @var $bundle \Symfony\Component\HttpKernel\Bundle\BundleInterface */
            foreach ($this->kernel->getBundles() as $bundle)
            {
                $path = $bundle->getPath() . "/" . $versionDirectory;
                if (is_dir($path)) {
                    $paths[] = $path;
                }
            }
        }

        $definitions = array();
        foreach ($paths as $path) {
            if (is_file($path)) {
                $definitions[basename($path)] = $returnFilename ? $path : new WorkflowDefinition(
                    basename($path),
                    $path,
                    file_get_contents($path)
                );
            } elseif (is_dir($path)) {
                foreach (new \DirectoryIterator($path) as $file) {
                    if ($file->isFile()) {
                        $definitions[$file->getFilename()] =
                            $returnFilename ? $file->getRealPath() : new WorkflowDefinition(
                                $file->getFilename(),
                                $file->getRealPath(),
                                file_get_contents($file->getRealPath())
                            );
                    }
                }
            } else {
                throw new \Exception("Path '$path' is neither a file nor directory");
            }
        }
        ksort($definitions);

        return $definitions;
    }
}
