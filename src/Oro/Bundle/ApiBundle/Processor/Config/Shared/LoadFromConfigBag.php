<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;

/**
 * Base processor to load raw configuration.
 */
abstract class LoadFromConfigBag implements ProcessorInterface
{
    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    /** @var EntityHierarchyProviderInterface */
    protected $entityHierarchyProvider;

    /**
     * @param ConfigLoaderFactory              $configLoaderFactory
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     */
    public function __construct(
        ConfigLoaderFactory $configLoaderFactory,
        EntityHierarchyProviderInterface $entityHierarchyProvider
    ) {
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
            if (!empty($config[ConfigUtil::DEFINITION])) {
                $context->setResult(
                    $this->configLoaderFactory->getLoader(ConfigUtil::DEFINITION)->load($config)
                );
            }
            $extras = $context->getExtras();
            foreach ($extras as $extra) {
                $sectionName = $extra->getName();
                if ($extra instanceof ConfigExtraSectionInterface
                    && !empty($config[$sectionName])
                    && !$context->has($sectionName)
                ) {
                    $context->set(
                        $sectionName,
                        $this->configLoaderFactory->getLoader($extra->getConfigType())->load($config[$sectionName])
                    );
                }
            }
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
        if (empty($config) || ConfigUtil::isInherit($config)) {
            $parentClasses = $this->entityHierarchyProvider->getHierarchyForClassName($entityClass);
            foreach ($parentClasses as $parentClass) {
                $parentConfig = $this->getConfig($parentClass, $version);
                if (!empty($parentConfig)) {
                    $config = $this->mergeConfigs($parentConfig, $config);
                    if (!ConfigUtil::isInherit($parentConfig)) {
                        break;
                    }
                }
            }
        }

        return null !== $config
            ? $config
            : [];
    }

    /**
     * @param array      $parentConfig
     * @param array|null $config
     *
     * @return array
     */
    protected function mergeConfigs($parentConfig, $config)
    {
        return null === $config
            ? $parentConfig
            : array_merge_recursive($parentConfig, $config);
    }

    /**
     * @param string $entityClass
     * @param string $version
     *
     * @return array|null
     */
    abstract protected function getConfig($entityClass, $version);
}
