<?php

namespace Oro\Bundle\ApiBundle\Config\Loader;

use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The factory to create configuration loaders for different API sections.
 */
class ConfigLoaderFactory
{
    private ConfigExtensionRegistry $extensionRegistry;
    /** @var ConfigLoaderInterface[]|null */
    private ?array $loaders = null;

    public function __construct(ConfigExtensionRegistry $extensionRegistry)
    {
        $this->extensionRegistry = $extensionRegistry;
    }

    /**
     * Indicates whether a loader for a given configuration type exists.
     */
    public function hasLoader(string $configType): bool
    {
        return null !== $this->findLoader($configType);
    }

    /**
     * Returns the loader that can be used to load a given configuration type.
     *
     * @throws \InvalidArgumentException if the loader was not found
     */
    public function getLoader(string $configType): ConfigLoaderInterface
    {
        $loader = $this->findLoader($configType);
        if (null === $loader) {
            throw new \InvalidArgumentException(sprintf(
                'The loader for the "%s" configuration was not found.',
                $configType
            ));
        }

        return $loader;
    }

    /**
     * Registers the loader for a given configuration type.
     * This method can be used to register a loader for new configuration type
     * or to override a default loader.
     */
    private function setLoader(string $configType, ConfigLoaderInterface $loader = null): void
    {
        if ($loader instanceof ConfigLoaderFactoryAwareInterface) {
            $loader->setConfigLoaderFactory($this);
        }
        $this->loaders[$configType] = $loader;
    }

    private function findLoader(string $configType): ?ConfigLoaderInterface
    {
        if (null === $this->loaders) {
            $this->loaders = [];

            $extensions = $this->extensionRegistry->getExtensions();
            foreach ($extensions as $extension) {
                $loaders = $extension->getEntityConfigurationLoaders();
                foreach ($loaders as $type => $loader) {
                    $this->setLoader($type, $loader);
                }
            }
        }

        if (\array_key_exists($configType, $this->loaders)) {
            return $this->loaders[$configType];
        }

        $loader = $this->createDefaultLoader($configType);
        $this->setLoader($configType, $loader);

        return $loader;
    }

    private function createDefaultLoader(string $configType): ?ConfigLoaderInterface
    {
        switch ($configType) {
            case ConfigUtil::DEFINITION:
                return new EntityDefinitionConfigLoader();
            case ConfigUtil::FIELDS:
                return new EntityDefinitionFieldConfigLoader();
            default:
                return null;
        }
    }
}
