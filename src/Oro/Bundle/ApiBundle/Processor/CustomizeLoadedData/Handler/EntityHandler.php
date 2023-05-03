<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\RootPathConfigExtra;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * The handler that can be used to modify loaded data for an entity.
 */
class EntityHandler
{
    private ActionProcessorInterface $customizationProcessor;
    private string $version;
    private RequestType $requestType;
    private string $entityClass;
    private EntityDefinitionConfig $config;
    /** @var ConfigExtraInterface[] */
    private array $configExtras;
    private bool $collection;
    private mixed $previousHandler;

    /**
     * @param ActionProcessorInterface $customizationProcessor
     * @param string                   $version
     * @param RequestType              $requestType
     * @param string                   $entityClass
     * @param EntityDefinitionConfig   $config
     * @param ConfigExtraInterface[]   $configExtras
     * @param bool                     $collection
     * @param callable|null            $previousHandler
     */
    public function __construct(
        ActionProcessorInterface $customizationProcessor,
        string $version,
        RequestType $requestType,
        string $entityClass,
        EntityDefinitionConfig $config,
        array $configExtras,
        bool $collection,
        ?callable $previousHandler = null
    ) {
        $this->customizationProcessor = $customizationProcessor;
        $this->version = $version;
        $this->requestType = $requestType;
        $this->entityClass = $entityClass;
        $this->config = $config;
        $this->configExtras = $configExtras;
        $this->collection = $collection;
        $this->previousHandler = $this->getPreviousHandler($previousHandler);
    }

    /**
     * Handles the given data.
     */
    public function __invoke(array $data, array $context): mixed
    {
        if (null !== $this->previousHandler) {
            $data = \call_user_func($this->previousHandler, $data, $context);
        }

        $customizationContext = $this->createCustomizationContext();
        $this->adjustPropertyPath($customizationContext);
        $customizationContext->setResult($data);
        $customizationContext->setIdentifierOnly(
            $this->isIdentifierOnlyRequested($customizationContext->getConfig())
        );
        $customizationContext->setSharedData($context['sharedData']);

        $group = $this->collection ? 'collection' : 'item';
        $customizationContext->setFirstGroup($group);
        $customizationContext->setLastGroup($group);

        $this->customizationProcessor->process($customizationContext);

        return $customizationContext->getResult();
    }

    /**
     * Creates the customization context based on the state of this handler.
     */
    protected function createCustomizationContext(): CustomizeLoadedDataContext
    {
        /** @var CustomizeLoadedDataContext $customizationContext */
        $customizationContext = $this->customizationProcessor->createContext();
        $customizationContext->setVersion($this->version);
        $customizationContext->getRequestType()->set($this->requestType);
        $customizationContext->setClassName($this->entityClass);
        $customizationContext->setConfig($this->config);
        $customizationContext->setConfigExtras($this->configExtras);

        return $customizationContext;
    }

    /**
     * Checks whether this handler does the same work as the given handler
     * and can be used instead of it.
     */
    protected function isRedundantHandler(callable $handler): bool
    {
        return
            $handler instanceof self
            && $this->version === $handler->version
            && (string)$this->requestType === (string)$handler->requestType
            && is_a($this->entityClass, $handler->entityClass, true);
    }

    private function adjustPropertyPath(CustomizeLoadedDataContext $customizationContext): void
    {
        /** @var RootPathConfigExtra|null $rootPathConfigExtra */
        $rootPathConfigExtra = $customizationContext->getConfigExtra(RootPathConfigExtra::NAME);
        if (null !== $rootPathConfigExtra) {
            /**
             * loading of additional entities, e.g.:
             * @see \Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\ExpandMultiTargetAssociations
             */
            $rootPath = $rootPathConfigExtra->getPath();
            $propertyPath = $customizationContext->getPropertyPath();
            if ($propertyPath) {
                $customizationContext->setPropertyPath($rootPath . ConfigUtil::PATH_DELIMITER . $propertyPath);
            } else {
                $customizationContext->setPropertyPath($rootPath);
            }
        }
    }

    /**
     * Returns a previous handler to be executed.
     */
    private function getPreviousHandler(?callable $previousHandler): ?callable
    {
        $result = null;
        while (null !== $previousHandler) {
            if (!$previousHandler instanceof self) {
                $result = $previousHandler;
                break;
            }
            if (!$this->isRedundantHandler($previousHandler)) {
                $result = $previousHandler;
                break;
            }
            $previousHandler = $previousHandler->previousHandler;
        }

        return $result;
    }

    private function isIdentifierOnlyRequested(?EntityDefinitionConfig $config): bool
    {
        return
            null !== $config
            && $config->isIdentifierOnlyRequested();
    }
}
