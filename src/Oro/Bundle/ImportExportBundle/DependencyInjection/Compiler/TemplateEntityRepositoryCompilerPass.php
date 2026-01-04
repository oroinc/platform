<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TemplateEntityRepositoryCompilerPass implements CompilerPassInterface
{
    public const TEMPLATE_MANAGER_KEY = 'oro_importexport.template_fixture.manager';
    public const TEMPLATE_FIXTURE_TAG = 'oro_importexport.template_fixture';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::TEMPLATE_MANAGER_KEY)) {
            return;
        }

        $definition     = $container->getDefinition(self::TEMPLATE_MANAGER_KEY);
        $taggedServices = $container->findTaggedServiceIds(self::TEMPLATE_FIXTURE_TAG);

        foreach ($taggedServices as $id => $tagAttributes) {
            $definition->addMethodCall('addEntityRepository', [new Reference($id)]);
        }
    }
}
