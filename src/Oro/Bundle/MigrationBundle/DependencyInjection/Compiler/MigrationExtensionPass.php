<?php

namespace Oro\Bundle\MigrationBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all migration extensions.
 */
class MigrationExtensionPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    private const MANAGER_SERVICE_KEY = 'oro_migration.migrations.extension_manager';
    private const TAG                 = 'oro_migration.extension';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::MANAGER_SERVICE_KEY)) {
            return;
        }

        $storageDefinition = $container->getDefinition(self::MANAGER_SERVICE_KEY);
        $extensions = $this->loadExtensions($container);
        foreach ($extensions as $extensionName => $extensionServiceId) {
            $storageDefinition->addMethodCall(
                'addExtension',
                [$extensionName, new Reference($extensionServiceId)]
            );
        }
    }

    private function loadExtensions(ContainerBuilder $container): array
    {
        $extensions = [];
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $id => $tags) {
            $container->getDefinition($id)->setPublic(false);
            $attributes = $tags[0];
            $priority = $this->getPriorityAttribute($attributes);
            $extensionName = $this->getRequiredAttribute($attributes, 'extension_name', $id, self::TAG);
            if (!isset($extensions[$extensionName])) {
                $extensions[$extensionName] = [];
            }
            $extensions[$extensionName][$priority] = $id;
        }

        $result = [];
        foreach ($extensions as $name => $extension) {
            if (count($extension) > 1) {
                krsort($extension);
            }
            $result[$name] = array_pop($extension);
        }

        return $result;
    }
}
