<?php

namespace Kaliop\eZWorkflowEngineBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TaggedServicesCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('ez_workflowengine_bundle.workflow_service')) {
            $workflowService = $container->findDefinition('ez_workflowengine_bundle.workflow_service');

            $DefinitionParsers = $container->findTaggedServiceIds('ez_workflowengine_bundle.definition_parser');
            foreach ($DefinitionParsers as $id => $tags) {
                $workflowService->addMethodCall('addDefinitionParser', array(
                    new Reference($id)
                ));
            }

            // avoid re-tagging manually all executors
            $executors = $container->findTaggedServiceIds('ez_migration_bundle.executor');
            foreach ($executors as $id => $tags) {
                $workflowService->addMethodCall('addExecutor', array(
                    new Reference($id)
                ));
            }
        }
    }
}
