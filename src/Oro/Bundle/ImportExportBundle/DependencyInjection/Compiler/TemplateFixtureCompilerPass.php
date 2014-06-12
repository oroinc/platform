<?php
namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TemplateFixtureCompilerPass implements CompilerPassInterface
{
    const TEMPLATE_REGISTRY_KEY = 'oro_importexport.template_fixture.registry';
    const TEMPLATE_FIXTURE_TAG = 'oro_importexport.template_fixture';

    public function process(ContainerBuilder $container)
    {
        if (!$definition = $container->hasDefinition(self::TEMPLATE_REGISTRY_KEY)) {
            return;
        }

        $definition = $container->getDefinition(self::TEMPLATE_REGISTRY_KEY);
        $taggedServices = $container->findTaggedServiceIds(self::TEMPLATE_FIXTURE_TAG);

        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall('addEntityFixture', array($attributes['entity'], new Reference($id)));
            }
        }
    }
}
