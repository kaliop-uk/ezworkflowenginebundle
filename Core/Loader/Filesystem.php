<?php

namespace Kaliop\eZWorkflowEngineBundle\Core\Loader;

use Kaliop\eZMigrationBundle\Core\Loader\Filesystem as BaseLoader;
use Kaliop\eZWorkflowEngineBundle\API\Value\WorkflowDefinition;
use Kaliop\eZMigrationBundle\API\ConfigResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Filesystem extends BaseLoader
{
    public function __construct(KernelInterface $kernel, $versionDirectoryParameter = 'Workflows', ConfigResolverInterface $configResolver = null)
    {
        $this->versionDirectory = $configResolver ? $configResolver->getParameter($versionDirectoryParameter) : $versionDirectoryParameter;
        $this->kernel = $kernel;
    }

    /**
     * @param array $paths either dir names or file names
     * @param bool $returnFilename return either the
     * @return WorkflowDefinition[]|string[] migrations definitions. key: name, value: contents of the definition, as string or file path
     * @throws \Exception
     */
    protected function getDefinitions(array $paths = array(), $returnFilename = false)
    {
        // if no paths defined, we look in all bundles
        if (empty($paths)) {
            $paths = array();
            /** @var $bundle \Symfony\Component\HttpKernel\Bundle\BundleInterface */
            foreach ($this->kernel->getBundles() as $bundle)
            {
                $path = $bundle->getPath() . "/" . $this->versionDirectory;
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
