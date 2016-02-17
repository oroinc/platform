<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
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
        /** @var ConfigContext $context */

        if ($context->hasResult()) {
            // config is already set
            return;
        }

        $config = $this->loadConfig($context->getClassName(), $context->getVersion());
        if (null !== $config) {
            $context->setResult($config);
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
        $config = $this->configBag->getConfig($entityClass, $version);
        if (empty($config) || ConfigUtil::isInherit($config)) {
            $parentClasses = $this->entityHierarchyProvider->getHierarchyForClassName($entityClass);
            foreach ($parentClasses as $parentClass) {
                $parentConfig = $this->configBag->getConfig($parentClass, $version);
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
