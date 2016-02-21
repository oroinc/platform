<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ConfigLoaderFactory
{
    /** @var ConfigLoaderInterface[] */
    private $loaders = [];

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
     * @param string                $configType
     * @param ConfigLoaderInterface $loader
     */
    public function setLoader($configType, ConfigLoaderInterface $loader)
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
        if (array_key_exists($configType, $this->loaders)) {
            return $this->loaders[$configType];
        }

        $loader = $this->createDefaultLoader($configType);
        if ($loader instanceof ConfigLoaderFactoryAwareInterface) {
            $loader->setConfigLoaderFactory($this);
        }
        $this->loaders[$configType] = $loader;

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
            case ConfigUtil::FILTERS:
                return new FiltersConfigLoader();
            case ConfigUtil::SORTERS:
                return new SortersConfigLoader();
            default:
                return null;
        }
    }
}
