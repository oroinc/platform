<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ConfigLoaderFactory
{
    /** @var ConfigExtensionRegistry */
    protected $extensionRegistry;

    /** @var ConfigLoaderInterface[] */
    private $loaders;

    /**
     * @param ConfigExtensionRegistry $extensionRegistry
     */
    public function __construct(ConfigExtensionRegistry $extensionRegistry)
    {
        $this->extensionRegistry = $extensionRegistry;
    }

    /**
     * Indicates whether a loader for a given configuration type exists.
     *
     * @param string $configType
     *
     * @return bool
     */
    public function hasLoader($configType)
    {
        return null !== $this->findLoader($configType);
    }

    /**
     * Returns the loader that can be used to load a given configuration type.
     *
     * @param string $configType
     *
     * @return ConfigLoaderInterface
     *
     * @throws \InvalidArgumentException if the loader was not found
     */
    public function getLoader($configType)
    {
        $loader = $this->findLoader($configType);
        if (null === $loader) {
            throw new \InvalidArgumentException(
                sprintf('The loader for the "%s" configuration was not found.', $configType)
            );
        }

        return $loader;
    }

    /**
     * Registers the loader for a given configuration type.
     * This method can be used to register a loader for new configuration type
     * or to override a default loader.
     *
     * @param string                     $configType
     * @param ConfigLoaderInterface|null $loader
     */
    protected function setLoader($configType, ConfigLoaderInterface $loader = null)
    {
        if ($loader instanceof ConfigLoaderFactoryAwareInterface) {
            $loader->setConfigLoaderFactory($this);
        }
        $this->loaders[$configType] = $loader;
    }

    /**
     * @param string $configType
     *
     * @return ConfigLoaderInterface|null
     */
    protected function findLoader($configType)
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

        if (array_key_exists($configType, $this->loaders)) {
            return $this->loaders[$configType];
        }

        $loader = $this->createDefaultLoader($configType);
        $this->setLoader($configType, $loader);

        return $loader;
    }

    /**
     * @param string $configType
     *
     * @return ConfigLoaderInterface|null
     */
    protected function createDefaultLoader($configType)
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
