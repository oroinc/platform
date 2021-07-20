<?php

namespace Oro\Bundle\GaufretteBundle\DependencyInjection\Compiler;

use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Checks configuration of all Gaufrette file managers
 * and marks those that use "private" and "public" Gaufrette adapters as a sub-directory aware file managers.
 */
class ConfigureGaufretteFileManagersPass implements CompilerPassInterface
{
    private const SUB_DIRECTORY_AWARE_FILESYSTEM_NAMES = ['private', 'public'];

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $filesystemMap = $this->getFilesystemMap($container);
        $definitions = $container->getDefinitions();
        foreach ($definitions as $serviceId => $def) {
            if ($def instanceof ChildDefinition && $def->getParent() === 'oro_gaufrette.file_manager') {
                $this->configureFileManager($def, $serviceId, $container, $filesystemMap);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array [filesystem name => adapter name, ...]
     */
    private function getFilesystemMap(ContainerBuilder $container): array
    {
        $filesystemMap = [];
        $gaufretteConfigs = $container->getExtensionConfig('knp_gaufrette');
        foreach ($gaufretteConfigs as $config) {
            if (empty($config['filesystems'])) {
                continue;
            }
            foreach ($config['filesystems'] as $filesystemName => $filesystemConfig) {
                if (!empty($filesystemConfig['adapter'])) {
                    $filesystemMap[$filesystemName] = $filesystemConfig['adapter'];
                }
            }
        }

        return $filesystemMap;
    }

    /**
     * @param Definition       $def
     * @param string           $serviceId
     * @param ContainerBuilder $container
     * @param array            $filesystemMap [filesystem name => adapter name, ...]
     */
    private function configureFileManager(
        Definition $def,
        string $serviceId,
        ContainerBuilder $container,
        array $filesystemMap
    ): void {
        if ($def->isAbstract()) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" service must be abstract.',
                $serviceId
            ));
        }

        $className = $this->getClassName($def, $container);
        if (!is_a($className, FileManager::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'The class of the "%s" service must be "%s" or has this class as one of its parents.',
                $serviceId,
                FileManager::class
            ));
        }

        $filesystemName = $this->getFilesystemName($def, $container);
        if (!$filesystemName) {
            throw new InvalidArgumentException(sprintf(
                'The first argument of the "%s" service must be the name of a Gaufrette filesystem.',
                $serviceId
            ));
        }

        if (!isset($filesystemMap[$filesystemName])) {
            throw new InvalidArgumentException(sprintf(
                'The Gaufrette filesystem "%s" is used by the "%s" service is not defined. Known filesystems: %s.',
                $filesystemName,
                $serviceId,
                implode(', ', array_keys($filesystemMap))
            ));
        }

        if (\in_array($filesystemMap[$filesystemName], self::SUB_DIRECTORY_AWARE_FILESYSTEM_NAMES, true)) {
            $def->addMethodCall('useSubDirectory', [true]);
        }
    }

    private function getClassName(Definition $def, ContainerBuilder $container): ?string
    {
        $className = $def->getClass();
        if (!$className) {
            return FileManager::class;
        }

        return $container->getParameterBag()->resolveValue($className);
    }

    private function getFilesystemName(Definition $def, ContainerBuilder $container): ?string
    {
        $arguments = $def->getArguments();
        if (!$arguments) {
            return null;
        }
        $firstArgument = reset($arguments);
        if (!\is_string($firstArgument)) {
            return null;
        }

        return $container->getParameterBag()->resolveValue($firstArgument);
    }
}
