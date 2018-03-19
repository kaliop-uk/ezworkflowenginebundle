<?php

namespace Kaliop\eZWorkflowEngineBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration extends SiteAccessConfiguration
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ez_workflowengine_bundle');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $systemNode  = $this->generateScopeBaseNode( $rootNode );
        $systemNode
            #->scalarNode( 'table_name' )->defaultValue( 'kaliop_workflows' )->end()
            ->scalarNode( 'workflow_directory' )->defaultValue( 'WorkflowDefinitions' )->end()
            #->scalarNode( 'enable_debug_output' )->defaultValue( 'true' )->end()
            #->integerNode( 'recursion_limit' )->defaultValue( '100' )->end()
        ->end();

        return $treeBuilder;
    }
}
