<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * The handler that can be used to modify loaded data for an entity association.
 */
class AssociationHandler extends EntityHandler
{
    private string $rootEntityClass;
    private string $propertyPath;

    /**
     * @param ActionProcessorInterface $customizationProcessor
     * @param string                   $version
     * @param RequestType              $requestType
     * @param string                   $rootEntityClass
     * @param string                   $propertyPath
     * @param string                   $entityClass
     * @param EntityDefinitionConfig   $config
     * @param ConfigExtraInterface[]   $configExtras
     * @param bool                     $collection
     * @param callable|null            $previousHandler
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ActionProcessorInterface $customizationProcessor,
        string $version,
        RequestType $requestType,
        string $rootEntityClass,
        string $propertyPath,
        string $entityClass,
        EntityDefinitionConfig $config,
        array $configExtras,
        bool $collection,
        ?callable $previousHandler = null
    ) {
        $this->rootEntityClass = $rootEntityClass;
        $this->propertyPath = $propertyPath;
        parent::__construct(
            $customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            $collection,
            $previousHandler
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function createCustomizationContext(array $context): CustomizeLoadedDataContext
    {
        $customizationContext = parent::createCustomizationContext($context);
        $customizationContext->setRootClassName($this->rootEntityClass);
        $customizationContext->setPropertyPath($this->propertyPath);

        /** @var EntityDefinitionConfig $config */
        $config = $context['config'][$this->rootEntityClass] ?? $customizationContext->getConfig();
        $customizationContext->setRootConfig($config);
        $customizationContext->setConfig($config->findFieldByPath($this->propertyPath)?->getTargetEntity());

        return $customizationContext;
    }

    /**
     * {@inheritDoc}
     */
    protected function isRedundantHandler(callable $handler): bool
    {
        return
            $handler instanceof self
            && $this->propertyPath === $handler->propertyPath
            && is_a($this->rootEntityClass, $handler->rootEntityClass, true)
            && parent::isRedundantHandler($handler);
    }
}
