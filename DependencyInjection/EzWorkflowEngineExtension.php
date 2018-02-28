<?php

namespace Kaliop\eZWorkflowEngineBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;


/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class EzWorkflowEngineExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return "ez_workflow_engine";
    }


    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration( $configuration, $configs );

        $loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__ . '/../Resources/config' ) );
        $loader->load( 'services.yml' );
        $loader->load( 'default_settings.yml' );

        $processor = new ConfigurationProcessor( $container, 'ez_workflowengine_bundle' );
        #$processor->mapSetting( 'table_name', $config );
        $processor->mapSetting( 'workflow_directory', $config );
        #$processor->mapSetting( 'enable_debug_output', $config );
        #$processor->mapSetting( 'recursion_limit', $config );
    }
}
