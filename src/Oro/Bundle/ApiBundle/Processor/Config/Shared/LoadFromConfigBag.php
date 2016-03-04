<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;

/**
 * Base processor to load raw configuration.
 */
abstract class LoadFromConfigBag implements ProcessorInterface
{
    /** @var ConfigExtensionRegistry */
    protected $configExtensionRegistry;

    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    /** @var EntityHierarchyProviderInterface */
    protected $entityHierarchyProvider;

    /**
     * @param ConfigExtensionRegistry          $configExtensionRegistry
     * @param ConfigLoaderFactory              $configLoaderFactory
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     */
    public function __construct(
        ConfigExtensionRegistry $configExtensionRegistry,
        ConfigLoaderFactory $configLoaderFactory,
        EntityHierarchyProviderInterface $entityHierarchyProvider
    ) {
        $this->configExtensionRegistry = $configExtensionRegistry;
        $this->configLoaderFactory     = $configLoaderFactory;
        $this->entityHierarchyProvider = $entityHierarchyProvider;
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

        $config = $this->loadConfig($context->getClassName(), $context->getVersion());
        if (!empty($config)) {
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

            $sectionNames = $this->getAllConfigSectionNames();
            foreach ($sectionNames as $sectionName) {
                unset($config[$sectionName]);
            }

            $context->setResult(
                $this->loadConfigObject(ConfigUtil::DEFINITION, $config)
            );
        }
    }

    /**
     * @param string $entityClass
     * @param string $version
     *
     * @return array
     */
    protected function loadConfig($entityClass, $version)
    {
        $config = $this->getConfig($entityClass, $version);
        if (null === $config) {
            $config = [];
        }
        $isInherit = $this->isInherit($config);
        if (array_key_exists(ConfigUtil::INHERIT, $config)) {
            unset($config[ConfigUtil::INHERIT]);
        }
        if ($isInherit) {
            $parentClasses = $this->entityHierarchyProvider->getHierarchyForClassName($entityClass);
            foreach ($parentClasses as $parentClass) {
                $parentConfig = $this->getConfig($parentClass, $version);
                if (!empty($parentConfig)) {
                    $isInherit = $this->isInherit($parentConfig);
                    if (array_key_exists(ConfigUtil::INHERIT, $parentConfig)) {
                        unset($parentConfig[ConfigUtil::INHERIT]);
                    }
                    $config = empty($config)
                        ? $parentConfig
                        : $this->mergeConfigs($config, $parentConfig);
                    if (!$isInherit) {
                        break;
                    }
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
    public static function isInherit(array $config)
    {
        return
            !isset($config[ConfigUtil::INHERIT])
            || $config[ConfigUtil::INHERIT];
    }

    /**
     * @return string[]
     */
    protected function getAllConfigSectionNames()
    {
        $sectionNameMap = [];
        $extensions     = $this->configExtensionRegistry->getExtensions();
        foreach ($extensions as $extension) {
            $sections = $extension->getEntityConfigurationSections();
            foreach ($sections as $name => $configuration) {
                if (!isset($sectionNameMap[$name])) {
                    $sectionNameMap[$name] = true;
                }
            }
        }

        return array_keys($sectionNameMap);
    }

    /**
     * @param string $entityClass
     * @param string $version
     *
     * @return array|null
     */
    abstract protected function getConfig($entityClass, $version);

    /**
     * @param array $config
     * @param array $parentConfig
     *
     * @return array
     */
    abstract protected function mergeConfigs(array $config, array $parentConfig);
}
