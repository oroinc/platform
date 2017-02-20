<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\LoadFromConfigBag as BaseLoadFromConfigBag;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeActionConfigHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeEntityConfigHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeParentResourceHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeSubresourceConfigHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ResourceHierarchyProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Loads configuration from "Resources/config/oro/api.yml".
 */
class LoadFromConfigBag extends BaseLoadFromConfigBag
{
    /** @var ConfigBag */
    private $configBag;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    /** @var MergeParentResourceHelper */
    private $mergeParentResourceHelper;

    /** @var MergeActionConfigHelper */
    private $mergeActionConfigHelper;

    /** @var MergeSubresourceConfigHelper */
    private $mergeSubresourceConfigHelper;

    /** @var string|null */
    private $entityClass;

    /** @var string|null */
    private $parentResourceClass;

    /**
     * @param ConfigExtensionRegistry      $configExtensionRegistry
     * @param ConfigLoaderFactory          $configLoaderFactory
     * @param ResourceHierarchyProvider    $resourceHierarchyProvider
     * @param ConfigBag                    $configBag
     * @param ResourcesProvider            $resourcesProvider
     * @param MergeParentResourceHelper    $mergeParentResourceHelper
     * @param MergeEntityConfigHelper      $mergeEntityConfigHelper
     * @param MergeActionConfigHelper      $mergeActionConfigHelper
     * @param MergeSubresourceConfigHelper $mergeSubresourceConfigHelper
     */
    public function __construct(
        ConfigExtensionRegistry $configExtensionRegistry,
        ConfigLoaderFactory $configLoaderFactory,
        ResourceHierarchyProvider $resourceHierarchyProvider,
        ConfigBag $configBag,
        ResourcesProvider $resourcesProvider,
        MergeParentResourceHelper $mergeParentResourceHelper,
        MergeEntityConfigHelper $mergeEntityConfigHelper,
        MergeActionConfigHelper $mergeActionConfigHelper,
        MergeSubresourceConfigHelper $mergeSubresourceConfigHelper
    ) {
        parent::__construct(
            $configExtensionRegistry,
            $configLoaderFactory,
            $resourceHierarchyProvider,
            $mergeEntityConfigHelper
        );
        $this->configBag = $configBag;
        $this->resourcesProvider = $resourcesProvider;
        $this->mergeParentResourceHelper = $mergeParentResourceHelper;
        $this->mergeActionConfigHelper = $mergeActionConfigHelper;
        $this->mergeSubresourceConfigHelper = $mergeSubresourceConfigHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function processConfig(ConfigContext $context)
    {
        $this->entityClass = $context->getClassName();
        $config = null;
        $parentResourceClass = null;
        try {
            $config = $this->loadConfig($this->entityClass, $context->getVersion(), $context->getRequestType());
            $parentResourceClass = $this->parentResourceClass;
        } finally {
            $this->entityClass = null;
            $this->parentResourceClass = null;
        }
        if (null !== $config) {
            $this->saveConfig($context, $config);
            if (null !== $parentResourceClass) {
                $this->mergeParentResourceHelper->mergeParentResourceConfig($context, $parentResourceClass);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function saveConfig(ConfigContext $context, array $config)
    {
        $action = $context->getTargetAction();
        if ($action) {
            if (!empty($config[ConfigUtil::ACTIONS][$action])) {
                $config = $this->mergeActionConfigHelper->mergeActionConfig(
                    $config,
                    $config[ConfigUtil::ACTIONS][$action],
                    $context->hasExtra(DescriptionsConfigExtra::NAME)
                );
            }
            $association = $context->getAssociationName();
            if ($association) {
                $parentConfig = $this->loadConfig(
                    $context->getParentClassName(),
                    $context->getVersion(),
                    $context->getRequestType()
                );
                if (!empty($parentConfig[ConfigUtil::SUBRESOURCES][$association])) {
                    $config = $this->mergeSubresourceConfigHelper->mergeSubresourcesConfig(
                        $config,
                        $parentConfig[ConfigUtil::SUBRESOURCES][$association],
                        $action,
                        $context->hasExtra(DescriptionsConfigExtra::NAME),
                        $context->hasExtra(FiltersConfigExtra::NAME)
                    );
                }
            }
        }

        parent::saveConfig($context, $config);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfig($entityClass, $version, RequestType $requestType)
    {
        if ($this->entityClass
            && $entityClass !== $this->entityClass
            && !$this->parentResourceClass
            && $this->resourcesProvider->isResourceKnown($entityClass, $version, $requestType)
        ) {
            /**
             * remember the class name of parent API resource and stop processing of other parents
             * @see processConfig
             */
            $this->parentResourceClass = $entityClass;

            return false;
        }

        return $this->configBag->getConfig($entityClass, $version);
    }
}
