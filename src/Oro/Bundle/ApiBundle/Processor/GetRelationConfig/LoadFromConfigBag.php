<?php

namespace Oro\Bundle\ApiBundle\Processor\GetRelationConfig;

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

        $entityClass = $context->getClassName();
        if (!$entityClass) {
            // an entity type is not specified
            return;
        }

        $config = $this->configBag->getRelationConfig($entityClass, $context->getVersion());
        if (null === $config || ConfigUtil::isRelationInherit($config)) {
            $parentClasses = $this->entityHierarchyProvider->getHierarchyForClassName($entityClass);
            foreach ($parentClasses as $parentClass) {
                $parentConfig = $this->configBag->getRelationConfig($parentClass, $context->getVersion());
                if (!empty($parentConfig)) {
                    if (null === $config) {
                        $config = $parentConfig;
                    } else {
                        $config = array_merge_recursive($parentConfig, $config);
                    }
                    if (!ConfigUtil::isRelationInherit($parentConfig)) {
                        break;
                    }
                }
            }
        }
        if (null !== $config) {
            $context->setResult($config);
        }
    }
}
