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
echo 'AAA?';
        if ($container->has('ez_workflowengine_bundle.workflow_service')) {
echo 'AAA!';
            $migrationService = $container->findDefinition('ez_workflowengine_bundle.workflow_service');

            $DefinitionParsers = $container->findTaggedServiceIds('ez_workflowengine_bundle.definition_parser');
            foreach ($DefinitionParsers as $id => $tags) {
echo 'BBB!';
                $migrationService->addMethodCall('addDefinitionParser', array(
                    new Reference($id)
                ));
            }
        }
    }
}
