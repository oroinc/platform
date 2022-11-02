<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\SubresourceConfig;
use Oro\Bundle\ApiBundle\Config\SubresourcesConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Provider\ConfigBagRegistry;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Loads sub-resources configured in "Resources/config/oro/api.yml".
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LoadFromConfigBag extends LoadSubresources
{
    private ConfigLoaderFactory $configLoaderFactory;
    private ConfigBagRegistry $configBagRegistry;

    public function __construct(
        ConfigLoaderFactory $configLoaderFactory,
        ConfigBagRegistry $configBagRegistry,
        ConfigProvider $configProvider,
        MetadataProvider $metadataProvider
    ) {
        parent::__construct($configProvider, $metadataProvider);
        $this->configLoaderFactory = $configLoaderFactory;
        $this->configBagRegistry = $configBagRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CollectSubresourcesContext $context */

        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $accessibleResources = array_fill_keys($context->getAccessibleResources(), true);
        $subresources = $context->getResult();

        $resources = $context->getResources();
        foreach ($resources as $entityClass => $resource) {
            if (!SubresourceUtil::isSubresourcesEnabled($resource)) {
                continue;
            }
            $subresourceConfigs = $this->getSubresourceConfigs($entityClass, $version, $requestType);
            if (empty($subresourceConfigs)) {
                continue;
            }

            $entitySubresources = $subresources->get($entityClass);
            if (null === $entitySubresources) {
                $entitySubresources = new ApiResourceSubresources($entityClass);
                $subresources->add($entitySubresources);
            }
            $this->processEntitySubresources(
                $resource,
                $entitySubresources,
                $subresourceConfigs,
                $entityClass,
                $version,
                $requestType,
                $accessibleResources
            );
        }
    }

    /**
     * @param ApiResource             $resource
     * @param ApiResourceSubresources $entitySubresources
     * @param SubresourceConfig[]     $subresourceConfigs
     * @param string                  $entityClass
     * @param string                  $version
     * @param RequestType             $requestType
     * @param array                   $accessibleResources
     */
    private function processEntitySubresources(
        ApiResource $resource,
        ApiResourceSubresources $entitySubresources,
        array $subresourceConfigs,
        string $entityClass,
        string $version,
        RequestType $requestType,
        array $accessibleResources
    ): void {
        foreach ($subresourceConfigs as $associationName => $subresourceConfig) {
            if ($subresourceConfig->isExcluded()) {
                $entitySubresources->removeSubresource($associationName);
                continue;
            }

            $subresource = $entitySubresources->getSubresource($associationName);
            if (null === $subresource) {
                $association = $this->getAssociationMetadata($entityClass, $associationName, $version, $requestType);
                try {
                    if (null === $association) {
                        $subresource = $this->createSubresourceFromConfig(
                            $subresourceConfig,
                            $accessibleResources
                        );
                    } else {
                        $subresource = $this->createSubresource(
                            $association,
                            $accessibleResources,
                            $this->getSubresourceExcludedActions($resource)
                        );
                        $this->updateSubresourceTargetFromConfig($subresource, $subresourceConfig);
                        $this->updateSubresourceActions($subresource, $subresourceConfig);
                    }
                } catch (\Throwable $e) {
                    throw new \RuntimeException(sprintf(
                        'Invalid configuration for "%s" subresource of "%s" entity. %s',
                        $associationName,
                        $entityClass,
                        $e->getMessage()
                    ));
                }
                $entitySubresources->addSubresource($associationName, $subresource);
            } else {
                $this->validateExistingSubresource(
                    $entityClass,
                    $associationName,
                    $subresource,
                    $subresourceConfig
                );
                $this->updateSubresourceActions($subresource, $subresourceConfig);
            }
        }
    }

    /**
     * @return SubresourceConfig[] [association name => SubresourceConfig, ...]
     */
    private function getSubresourceConfigs(string $entityClass, string $version, RequestType $requestType): array
    {
        $subresourcesConfig = $this->getSubresourcesConfig($entityClass, $version, $requestType);
        if (null === $subresourcesConfig || $subresourcesConfig->isEmpty()) {
            return [];
        }

        return $subresourcesConfig->getSubresources();
    }

    private function createSubresourceFromConfig(
        SubresourceConfig $subresourceConfig,
        array $accessibleResources
    ): ApiSubresource {
        if (!$subresourceConfig->getTargetClass()) {
            throw new \RuntimeException('The target class should be specified in config.');
        }

        $targetClassName = $subresourceConfig->getTargetClass();

        $subresource = new ApiSubresource();
        $subresource->setTargetClassName($targetClassName);
        SubresourceUtil::setAcceptableTargetClasses($subresource, $targetClassName);
        $subresource->setIsCollection($subresourceConfig->isCollectionValuedAssociation());
        SubresourceUtil::setSubresourceExcludedActions(
            $subresource,
            $accessibleResources,
            SubresourceUtil::SUBRESOURCE_ACTIONS_WITHOUT_GET_SUBRESOURCE
        );
        $actions = $subresourceConfig->getActions();
        foreach ($actions as $actionName => $action) {
            if (!$action->isExcluded() && $subresource->isExcludedAction($actionName)) {
                $subresource->removeExcludedAction($actionName);
            }
        }

        return $subresource;
    }

    private function updateSubresourceTargetFromConfig(
        ApiSubresource $subresource,
        SubresourceConfig $subresourceConfig
    ): void {
        $targetClassName = $subresourceConfig->getTargetClass();

        if ($targetClassName) {
            $subresource->setTargetClassName($targetClassName);
            SubresourceUtil::setAcceptableTargetClasses($subresource, $targetClassName);
            $subresource->setIsCollection($subresourceConfig->isCollectionValuedAssociation());
        }
    }

    private function getAssociationMetadata(
        string $entityClass,
        string $associationName,
        string $version,
        RequestType $requestType
    ): ?AssociationMetadata {
        $config = $this->getConfig($entityClass, $version, $requestType);
        if (null === $config) {
            return null;
        }

        $resolvedAssociationName = $config->findFieldNameByPropertyPath($associationName);
        if (!$resolvedAssociationName) {
            return null;
        }

        $metadata = $this->getMetadata($entityClass, $version, $requestType, $config);
        if (null === $metadata) {
            return null;
        }

        return $metadata->getAssociation($resolvedAssociationName);
    }

    private function validateExistingSubresource(
        string $entityClass,
        string $associationName,
        ApiSubresource $subresource,
        SubresourceConfig $subresourceConfig
    ): void {
        $targetClass = $subresourceConfig->getTargetClass();
        if ($targetClass && $targetClass !== $subresource->getTargetClassName()) {
            throw new \RuntimeException(sprintf(
                'The target class for "%s" subresource of "%s" entity'
                . ' cannot be overridden by a configuration.'
                . 'Existing target class: %s. Target class from a configuration: %s.',
                $associationName,
                $entityClass,
                $subresource->getTargetClassName(),
                $targetClass
            ));
        }
        if (($targetClass || $subresourceConfig->hasTargetType())
            && $subresourceConfig->isCollectionValuedAssociation() !== $subresource->isCollection()
        ) {
            throw new \RuntimeException(sprintf(
                'The target type for "%s" subresource of "%s" entity'
                . ' cannot be overridden by a configuration.'
                . 'Existing target type: %s. Target type from a configuration: %s.',
                $associationName,
                $entityClass,
                ConfigUtil::getAssociationTargetType($subresource->isCollection()),
                ConfigUtil::getAssociationTargetType($subresourceConfig->isCollectionValuedAssociation())
            ));
        }
    }

    private function updateSubresourceActions(ApiSubresource $subresource, SubresourceConfig $subresourceConfig): void
    {
        $actions = $subresourceConfig->getActions();
        foreach ($actions as $actionName => $action) {
            if ($action->hasExcluded()) {
                if ($action->isExcluded()) {
                    $subresource->addExcludedAction($actionName);
                } else {
                    $subresource->removeExcludedAction($actionName);
                }
            }
        }
    }

    /**
     * Loads configuration from the "subresources" section from "Resources/config/oro/api.yml"
     */
    private function getSubresourcesConfig(
        string $entityClass,
        string $version,
        RequestType $requestType
    ): ?SubresourcesConfig {
        $subresources = null;
        $config = $this->configBagRegistry->getConfigBag($requestType)->getConfig($entityClass, $version);
        if (null !== $config && !empty($config[ConfigUtil::SUBRESOURCES])) {
            $subresources = $this->loadSubresourcesConfig($config[ConfigUtil::SUBRESOURCES]);
            $actions = null;
            if (!empty($config[ConfigUtil::ACTIONS])) {
                $actions = $this->loadActionsConfig($config[ConfigUtil::ACTIONS]);
            }
            foreach ($subresources->getSubresources() as $subresource) {
                $this->updateSubresourceActionExclusion($subresource, ApiAction::UPDATE_SUBRESOURCE, $actions);
                $this->updateSubresourceActionExclusion($subresource, ApiAction::ADD_SUBRESOURCE, $actions);
                $this->updateSubresourceActionExclusion($subresource, ApiAction::DELETE_SUBRESOURCE, $actions);
            }
        }

        return $subresources;
    }

    private function updateSubresourceActionExclusion(
        SubresourceConfig $subresource,
        string $actionName,
        ?ActionsConfig $actions
    ): void {
        $subresourceAction = $subresource->getAction($actionName);
        if (null !== $subresourceAction && !$subresourceAction->hasExcluded()) {
            $action = $actions?->getAction($actionName);
            if (null === $action) {
                $subresourceAction->setExcluded(false);
            } elseif ($action->isExcluded()) {
                $subresourceAction->setExcluded(true);
            } else {
                $subresourceAction->setExcluded(false);
            }
        }
    }

    private function loadSubresourcesConfig(array $subresourcesConfig): SubresourcesConfig
    {
        return $this->configLoaderFactory->getLoader(ConfigUtil::SUBRESOURCES)->load($subresourcesConfig);
    }

    private function loadActionsConfig(array $actionsConfig): ActionsConfig
    {
        return $this->configLoaderFactory->getLoader(ConfigUtil::ACTIONS)->load($actionsConfig);
    }
}
