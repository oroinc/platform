<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that registers template entity repositories with the template manager.
 *
 * This pass collects all services tagged with `oro_importexport.template_fixture`,
 * and registers them with the template manager. These repositories provide template
 * fixture data for entities, which is used to generate export templates and validate
 * import data structure.
 */
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
