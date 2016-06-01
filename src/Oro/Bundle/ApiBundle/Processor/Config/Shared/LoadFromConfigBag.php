<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\NodeInterface;

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

    /** @var NodeInterface */
    private $configurationTree;

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
            $this->saveConfig($context, $config);
        }
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

        $sectionNames = $this->getAllConfigSectionNames();
        foreach ($sectionNames as $sectionName) {
            unset($config[$sectionName]);
        }

        $context->setResult(
            $this->loadConfigObject(ConfigUtil::DEFINITION, $config)
        );
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
        $isInherit = true;
        if (null !== $config) {
            $isInherit = $this->getInheritAndThenRemoveIt($config);
        } else {
            $config = [];
        }
        if ($isInherit) {
            $configs = [$config];
            $parentClasses = $this->entityHierarchyProvider->getHierarchyForClassName($entityClass);
            foreach ($parentClasses as $parentClass) {
                $config = $this->getConfig($parentClass, $version);
                if (!empty($config)) {
                    $isInherit = $this->getInheritAndThenRemoveIt($config);
                    $configs[] = $config;
                    if (!$isInherit) {
                        break;
                    }
                }
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
    public static function getInheritAndThenRemoveIt(array &$config)
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
     * @param array $config
     * @param array $parentConfig
     *
     * @return array
     */
    protected function mergeConfigs(array $config, array $parentConfig)
    {
        $processor = new Processor();

        return $processor->process($this->getConfigurationTree(), [$parentConfig, $config]);
    }

    /**
     * @return NodeInterface
     */
    protected function getConfigurationTree()
    {
        if (null === $this->configurationTree) {
            $this->configurationTree = $this->createConfigurationTree();
        }

        return $this->configurationTree;
    }

    /**
     * @param string $entityClass
     * @param string $version
     *
     * @return array|null
     */
    abstract protected function getConfig($entityClass, $version);

    /**
     * @return NodeInterface
     */
    abstract protected function createConfigurationTree();
}
