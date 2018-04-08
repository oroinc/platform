<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\EntityConfigMerger;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Base processor to load raw configuration.
 */
abstract class LoadFromConfigBag implements ProcessorInterface
{
    /** @var ConfigExtensionRegistry */
    protected $configExtensionRegistry;

    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    /** @var EntityConfigMerger */
    private $entityConfigMerger;

    /**
     * @param ConfigExtensionRegistry $configExtensionRegistry
     * @param ConfigLoaderFactory     $configLoaderFactory
     * @param EntityConfigMerger      $entityConfigMerger
     */
    public function __construct(
        ConfigExtensionRegistry $configExtensionRegistry,
        ConfigLoaderFactory $configLoaderFactory,
        EntityConfigMerger $entityConfigMerger
    ) {
        $this->configExtensionRegistry = $configExtensionRegistry;
        $this->configLoaderFactory = $configLoaderFactory;
        $this->entityConfigMerger = $entityConfigMerger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        if ($context->hasResult()) {
            // a config already exists
            return;
        }

        $this->processConfig($context);
    }

    /**
     * @param ConfigContext $context
     */
    protected function processConfig(ConfigContext $context)
    {
        $config = $this->loadConfig($context->getClassName(), $context->getVersion(), $context->getRequestType());
        $this->saveConfig($context, $config);
    }

    /**
     * @param ConfigContext $context
     * @param array         $config
     */
    protected function saveConfig(ConfigContext $context, array $config)
    {
        $extras = $context->getExtras();
        foreach ($extras as $extra) {
            $sectionName = $extra->getName();
            if ($extra instanceof ConfigExtraSectionInterface
                && !empty($config[$sectionName])
                && !$context->has($sectionName)
            ) {
                $context->set(
                    $sectionName,
                    $this->loadConfigObject($extra->getConfigType(), $config[$sectionName])
                );
            }
        }

        $sectionNames = $this->configExtensionRegistry->getConfigSectionNames();
        foreach ($sectionNames as $sectionName) {
            unset($config[$sectionName]);
        }

        if (!empty($config)) {
            $context->setResult(
                $this->loadConfigObject(ConfigUtil::DEFINITION, $config)
            );
        }
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array
     */
    protected function loadConfig($entityClass, $version, RequestType $requestType)
    {
        return $this->buildConfig($entityClass, $version, $requestType);
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array
     */
    protected function buildConfig($entityClass, $version, RequestType $requestType)
    {
        $config = $this->getConfig($entityClass, $version, $requestType);
        $isInherit = true;
        if (null === $config) {
            $config = [];
        } else {
            $isInherit = $this->getInheritAndThenRemoveIt($config);
        }
        if ($isInherit) {
            $configs = [$config];
            $parentClass = (new \ReflectionClass($entityClass))->getParentClass();
            while ($parentClass) {
                $config = $this->getConfig($parentClass->getName(), $version, $requestType);
                if (false === $config) {
                    break;
                }
                if (!empty($config)) {
                    $isInherit = $this->getInheritAndThenRemoveIt($config);
                    $configs[] = $config;
                    if (!$isInherit) {
                        break;
                    }
                }
                $parentClass = $parentClass->getParentClass();
            }
            if (count($configs) === 1) {
                $config = $configs[0];
            } else {
                $config = array_pop($configs);
                while (!empty($configs)) {
                    $config = $this->mergeConfigs(array_pop($configs), $config);
                }
            }
        }

        return $config;
    }

    /**
     * @param string $configType
     * @param array  $config
     *
     * @return object
     */
    protected function loadConfigObject($configType, $config)
    {
        return $this->configLoaderFactory->getLoader($configType)->load($config);
    }

    /**
     * @param array $config
     *
     * @return bool
     */
    protected function getInheritAndThenRemoveIt(array &$config)
    {
        if (array_key_exists(ConfigUtil::INHERIT, $config)) {
            $isInherit = $config[ConfigUtil::INHERIT];
            unset($config[ConfigUtil::INHERIT]);
        } else {
            $isInherit = true;
        }

        return $isInherit;
    }

    /**
     * @param array $config
     * @param array $parentConfig
     *
     * @return array
     */
    protected function mergeConfigs(array $config, array $parentConfig)
    {
        return $this->entityConfigMerger->merge($config, $parentConfig);
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array|false|null Returns an array if a configuration exists,
     *                          null if if a configuration does not exist,
     *                          and FALSE if loading of a configuration of parent classes should be stopped
     */
    abstract protected function getConfig($entityClass, $version, RequestType $requestType);
}
