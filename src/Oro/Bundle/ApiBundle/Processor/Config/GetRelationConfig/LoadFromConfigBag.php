<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;

class LoadFromConfigBag implements ProcessorInterface
{
    /** @var ConfigBag */
    protected $configBag;

    /** @var EntityHierarchyProviderInterface */
    protected $entityHierarchyProvider;

    /**
     * @param ConfigBag                        $configBag
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     */
    public function __construct(ConfigBag $configBag, EntityHierarchyProviderInterface $entityHierarchyProvider)
    {
        $this->configBag               = $configBag;
        $this->entityHierarchyProvider = $entityHierarchyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var RelationConfigContext $context */

        $config = $context->getResult();
        if (null !== $config) {
            // a config already exists
            return;
        }

        $config = $this->loadConfig($context->getClassName(), $context->getVersion());
        if (null !== $config) {
            $this->addConfigToContext($context, $config);
        }
    }

    /**
     * @param RelationConfigContext $context
     * @param array                 $config
     */
    protected function addConfigToContext(RelationConfigContext $context, array $config)
    {
        $hasDefinition = isset($config[ConfigUtil::DEFINITION]);
        if ($hasDefinition) {
            $context->setResult($config[ConfigUtil::DEFINITION]);
        }
        if (isset($config[ConfigUtil::FILTERS]) && null === $context->getFilters()) {
            $context->setFilters($config[ConfigUtil::FILTERS]);
        }
        if (isset($config[ConfigUtil::SORTERS]) && null === $context->getSorters()) {
            $context->setSorters($config[ConfigUtil::SORTERS]);
        }
        if ($hasDefinition) {
            if (null === $context->getFilters()) {
                $context->setFilters(ConfigUtil::getInitialConfig());
            }
            if (null === $context->getSorters()) {
                $context->setSorters(ConfigUtil::getInitialConfig());
            }
        }
    }

    /**
     * @param string $entityClass
     * @param string $version
     *
     * @return array|null
     */
    protected function loadConfig($entityClass, $version)
    {
        $config = $this->configBag->getRelationConfig($entityClass, $version);
        if (empty($config) || ConfigUtil::isInherit($config)) {
            $parentClasses = $this->entityHierarchyProvider->getHierarchyForClassName($entityClass);
            foreach ($parentClasses as $parentClass) {
                $parentConfig = $this->configBag->getRelationConfig($parentClass, $version);
                if (!empty($parentConfig)) {
                    $config = $this->mergeConfigs($parentConfig, $config);
                    if (!ConfigUtil::isInherit($parentConfig)) {
                        break;
                    }
                }
            }
        }

        return !empty($config) ? $config : null;
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
}
