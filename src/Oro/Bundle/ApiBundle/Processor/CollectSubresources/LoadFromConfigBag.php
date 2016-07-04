<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\SubresourceConfig;
use Oro\Bundle\ApiBundle\Config\SubresourcesConfig;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Loads sub-resources configured in "Resources/config/oro/api.yml".
 */
class LoadFromConfigBag implements ProcessorInterface
{
    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    /** @var ConfigBag */
    protected $configBag;

    /**
     * @param ConfigLoaderFactory $configLoaderFactory
     * @param ConfigBag           $configBag
     */
    public function __construct(
        ConfigLoaderFactory $configLoaderFactory,
        ConfigBag $configBag
    ) {
        $this->configLoaderFactory = $configLoaderFactory;
        $this->configBag = $configBag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectSubresourcesContext $context */

        $version = $context->getVersion();
        $resources = $context->getResources();
        $subresources = $context->getResult();
        foreach ($resources as $entityClass => $resource) {
            if (in_array(ApiActions::GET_SUBRESOURCE, $resource->getExcludedActions(), true)) {
                continue;
            }
            $subresourcesConfig = $this->getSubresourcesConfig($entityClass, $version);
            if (null === $subresourcesConfig || $subresourcesConfig->isEmpty()) {
                continue;
            }
            $subresourceConfigs = $subresourcesConfig->getSubresources();
            if (empty($subresourceConfigs)) {
                continue;
            }

            $entitySubresources = $subresources->get($entityClass);
            if (null === $entitySubresources) {
                $entitySubresources = new ApiResourceSubresources($entityClass);
                $subresources->add($entitySubresources);
            }

            foreach ($subresourceConfigs as $associationName => $subresourceConfig) {
                if ($subresourceConfig->isExcluded()) {
                    $entitySubresources->removeSubresource($associationName);
                } else {
                    $subresource = $entitySubresources->getSubresource($associationName);
                    if (null === $subresource) {
                        $subresource = $this->createNewSubresource($subresourceConfig);
                        $entitySubresources->addSubresource($associationName, $subresource);
                    } else {
                        $this->validateExistingSubresource(
                            $entityClass,
                            $associationName,
                            $subresource,
                            $subresourceConfig
                        );
                    }
                    $this->updateSubresourceActions($subresource, $subresourceConfig);
                }
            }
        }
    }

    /**
     * @param SubresourceConfig $subresourceConfig
     *
     * @return ApiSubresource
     */
    protected function createNewSubresource(SubresourceConfig $subresourceConfig)
    {
        $subresource = new ApiSubresource();
        $subresource->setTargetClassName($subresourceConfig->getTargetClass());
        $subresource->setAcceptableTargetClassNames([$subresourceConfig->getTargetClass()]);
        $subresource->setIsCollection($subresourceConfig->isCollectionValuedAssociation());

        return $subresource;
    }

    /**
     * @param string            $entityClass
     * @param string            $associationName
     * @param ApiSubresource    $subresource
     * @param SubresourceConfig $subresourceConfig
     */
    protected function validateExistingSubresource(
        $entityClass,
        $associationName,
        ApiSubresource $subresource,
        SubresourceConfig $subresourceConfig
    ) {
        if ($subresourceConfig->getTargetClass()) {
            throw new \RuntimeException(
                sprintf(
                    'The target class for "%s" subresource of "%s" entity'
                    . ' cannot be overridden by a configuration.'
                    . 'Existing target class: %s. Target class from a configuration: %s.',
                    $associationName,
                    $entityClass,
                    $subresource->getTargetClassName(),
                    $subresourceConfig->getTargetClass()
                )
            );
        }
        if ($subresourceConfig->getTargetType()) {
            throw new \RuntimeException(
                sprintf(
                    'The target type for "%s" subresource of "%s" entity'
                    . ' cannot be overridden by a configuration.'
                    . 'Existing target type: %s. Target type from a configuration: %s.',
                    $associationName,
                    $entityClass,
                    $subresource->isCollection() ? 'to-many' : 'to-one',
                    $subresourceConfig->isCollectionValuedAssociation() ? 'to-many' : 'to-one'
                )
            );
        }
    }

    /**
     * @param ApiSubresource    $subresource
     * @param SubresourceConfig $subresourceConfig
     */
    protected function updateSubresourceActions(ApiSubresource $subresource, SubresourceConfig $subresourceConfig)
    {
        $actions = $subresourceConfig->getActions();
        foreach ($actions as $actionName => $action) {
            if ($action->isExcluded()) {
                $subresource->addExcludedAction($actionName);
            }
        }
    }

    /**
     * Loads configuration from the "subresources" section from "Resources/config/oro/api.yml"
     *
     * @param string $entityClass
     * @param string $version
     *
     * @return SubresourcesConfig|null
     */
    protected function getSubresourcesConfig($entityClass, $version)
    {
        $subresources = null;
        $config = $this->configBag->getConfig($entityClass, $version);
        if (null !== $config && !empty($config[ConfigUtil::SUBRESOURCES])) {
            $subresourcesLoader = $this->configLoaderFactory->getLoader(ConfigUtil::SUBRESOURCES);
            $subresources = $subresourcesLoader->load($config[ConfigUtil::SUBRESOURCES]);
        }

        return $subresources;
    }
}
