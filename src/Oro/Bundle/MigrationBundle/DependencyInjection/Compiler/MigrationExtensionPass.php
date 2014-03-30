<?php

namespace Oro\Bundle\MigrationBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MigrationExtensionPass implements CompilerPassInterface
{
    const MANAGER_SERVICE_KEY = 'oro_migration.migrations.extension_manager';
    const TAG                 = 'oro_migration.extension';

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

    /**
     * Load migration extensions services
     *
     * @param ContainerBuilder $container
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function loadExtensions(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        $extensions     = [];
        foreach ($taggedServices as $id => $tagAttributes) {
            $priority = 0;
            $extensionName = null;
            foreach ($tagAttributes as $attributes) {
                if (!empty($attributes['priority'])) {
                    $priority = (int)$attributes['priority'];
                }
                if (!isset($attributes['extension_name']) || empty($attributes['extension_name'])) {
                    throw new InvalidConfigurationException(
                        sprintf('Tag attribute "extension_name" is required for "%s" service', $id)
                    );
                }
                $extensionName = $attributes['extension_name'];
            }
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
