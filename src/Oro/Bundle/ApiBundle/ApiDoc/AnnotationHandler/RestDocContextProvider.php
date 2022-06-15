<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler;

use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\DisabledAssociationsConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Symfony\Component\Routing\Route;

/**
 * Helps RestDocHandler to get API context, config and metadata.
 */
class RestDocContextProvider
{
    private const ACTION_TYPE_ATTRIBUTE = '_action_type';
    private const CONTROLLER_ATTRIBUTE = '_controller';
    private const ACTION_SUFFIX = 'Action';
    private const ITEM_WITHOUT_ID_ACTION = 'itemWithoutId';
    private const ITEM_ACTION = 'item';

    private RestDocViewDetector $docViewDetector;
    private ActionProcessorBagInterface $processorBag;

    public function __construct(RestDocViewDetector $docViewDetector, ActionProcessorBagInterface $processorBag)
    {
        $this->docViewDetector = $docViewDetector;
        $this->processorBag = $processorBag;
    }

    public function getContext(
        string $action,
        string $entityClass,
        string $associationName = null,
        Route $route = null
    ): Context|SubresourceContext {
        $processor = $this->processorBag->getProcessor($action);
        /** @var Context $context */
        $context = $processor->createContext();
        $context->addConfigExtra(new DisabledAssociationsConfigExtra());
        $context->addConfigExtra(new DescriptionsConfigExtra());
        $context->getRequestType()->set($this->docViewDetector->getRequestType());
        $context->setVersion($this->docViewDetector->getVersion());
        $context->setLastGroup(ApiActionGroup::INITIALIZE);
        $context->setMasterRequest(true);
        if ($associationName && $context instanceof SubresourceContext) {
            $context->setParentClassName($entityClass);
            $context->setAssociationName($associationName);
        } else {
            $context->setClassName($entityClass);
        }
        if (null !== $route && $context instanceof OptionsContext) {
            self::setActionType($context, $route, $action, $entityClass, $associationName);
        }

        $processor->process($context);

        return $context;
    }

    /**
     * @throws \LogicException if the configuration cannot be loaded
     */
    public function getConfig(Context $context): EntityDefinitionConfig
    {
        $config = $context->getConfig();
        if (null === $config) {
            throw self::createCannotBeLoadedException('configuration', $context);
        }

        return $config;
    }

    /**
     * @throws \LogicException if the metadata cannot be loaded
     */
    public function getMetadata(Context $context): EntityMetadata
    {
        $metadata = $context->getMetadata();
        if (null === $metadata) {
            throw self::createCannotBeLoadedException('metadata', $context);
        }

        return $metadata;
    }

    private static function createCannotBeLoadedException(string $type, Context $context): \LogicException
    {
        $message = sprintf(
            'The %s for "%s" cannot be loaded. Action: %s.',
            $type,
            $context->getClassName(),
            $context->getAction()
        );
        if ($context instanceof SubresourceContext) {
            $message .= sprintf(' Association: %s.', $context->getAssociationName());
        }

        return new \LogicException($message);
    }

    /**
     * @throws \LogicException if the action type cannot be set
     */
    private static function setActionType(
        OptionsContext $context,
        Route $route,
        string $action,
        string $entityClass,
        string $associationName = null
    ): void {
        $actionType = self::getActionType($route);
        if (!$actionType) {
            throw self::createActionTypeException(
                'The action type for cannot be determined.',
                $route,
                $action,
                $entityClass,
                $associationName
            );
        }
        try {
            $context->setActionType($actionType);
        } catch (\InvalidArgumentException $e) {
            throw self::createActionTypeException(
                $e->getMessage(),
                $route,
                $action,
                $entityClass,
                $associationName
            );
        }
    }

    private static function getActionType(Route $route): ?string
    {
        $actionType = $route->getOption(self::ACTION_TYPE_ATTRIBUTE);
        if (!$actionType) {
            $controller = self::getController($route);
            if (str_ends_with($controller, self::ACTION_SUFFIX)) {
                $startPos = strrpos($controller, '::');
                if (false !== $startPos) {
                    $startPos += 2;
                    $actionType = substr(
                        $controller,
                        $startPos,
                        \strlen($controller) - \strlen(self::ACTION_SUFFIX) - $startPos
                    );
                    if (self::ITEM_WITHOUT_ID_ACTION === $actionType) {
                        $actionType = self::ITEM_ACTION;
                    }
                }
            }
        }

        return $actionType;
    }

    private static function getController(Route $route): string
    {
        return $route->getDefault(self::CONTROLLER_ATTRIBUTE);
    }

    private static function createActionTypeException(
        string $message,
        Route $route,
        string $action,
        string $entityClass,
        string $associationName = null
    ): \LogicException {
        $message .= sprintf(' Entity Class: %s. Action: %s.', $entityClass, $action);
        if ($associationName) {
            $message .= sprintf(' Association: %s.', $associationName);
        }
        $message .= sprintf(
            ' Route Path: %s. Controller: %s. Use "%s" route option to explicitly set the action type.',
            $route->getPath(),
            self::getController($route),
            self::ACTION_TYPE_ATTRIBUTE
        );

        return new \LogicException($message);
    }
}
