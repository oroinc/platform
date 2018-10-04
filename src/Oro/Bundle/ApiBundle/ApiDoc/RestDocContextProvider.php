<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Symfony\Component\Routing\Route;

/**
 * Helps RestDocHandler to get Data API context, config and metadata.
 */
class RestDocContextProvider
{
    private const ACTION_TYPE_ATTRIBUTE  = '_action_type';
    private const CONTROLLER_ATTRIBUTE   = '_controller';
    private const ACTION_SUFFIX          = 'Action';
    private const ITEM_WITHOUT_ID_ACTION = 'itemWithoutId';
    private const ITEM_ACTION            = 'item';

    /** @var RestDocViewDetector */
    private $docViewDetector;

    /** @var ActionProcessorBagInterface */
    private $processorBag;

    /**
     * @param RestDocViewDetector         $docViewDetector
     * @param ActionProcessorBagInterface $processorBag
     */
    public function __construct(RestDocViewDetector $docViewDetector, ActionProcessorBagInterface $processorBag)
    {
        $this->docViewDetector = $docViewDetector;
        $this->processorBag = $processorBag;
    }

    /**
     * @param string      $action
     * @param string      $entityClass
     * @param string|null $associationName
     * @param Route|null  $route
     *
     * @return Context|SubresourceContext
     */
    public function getContext(
        string $action,
        string $entityClass,
        string $associationName = null,
        Route $route = null
    ): Context {
        $processor = $this->processorBag->getProcessor($action);
        /** @var Context $context */
        $context = $processor->createContext();
        $context->addConfigExtra(new DescriptionsConfigExtra());
        $context->getRequestType()->set($this->docViewDetector->getRequestType());
        $context->setVersion($this->docViewDetector->getVersion());
        $context->setLastGroup('initialize');
        if ($associationName && $context instanceof SubresourceContext) {
            $context->setParentClassName($entityClass);
            $context->setAssociationName($associationName);
            $context->addParentConfigExtra(new DescriptionsConfigExtra());
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
     * @param Context $context
     *
     * @return EntityDefinitionConfig
     *
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
     * @param Context $context
     *
     * @return EntityMetadata
     *
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

    /**
     * @param string  $type
     * @param Context $context
     *
     * @return \LogicException
     */
    private static function createCannotBeLoadedException(string $type, Context $context): \LogicException
    {
        $message = \sprintf(
            'The %s for "%s" cannot be loaded. Action: %s.',
            $type,
            $context->getClassName(),
            $context->getAction()
        );
        if ($context instanceof SubresourceContext) {
            $message .= \sprintf(' Association: %s.', $context->getAssociationName());
        }

        return new \LogicException($message);
    }

    /**
     * @param OptionsContext $context
     * @param Route          $route
     * @param string         $action
     * @param string         $entityClass
     * @param string|null    $associationName
     *
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

    /**
     * @param Route $route
     *
     * @return string|null
     */
    private static function getActionType(Route $route): ?string
    {
        $actionType = $route->getOption(self::ACTION_TYPE_ATTRIBUTE);
        if (!$actionType) {
            $controller = self::getController($route);
            if (self::endsWith($controller, self::ACTION_SUFFIX)) {
                $startPos = \strrpos($controller, '::');
                if (false !== $startPos) {
                    $startPos += 2;
                    $actionType = \substr(
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

    /**
     * @param Route $route
     *
     * @return string
     */
    private static function getController(Route $route): string
    {
        return $route->getDefault(self::CONTROLLER_ATTRIBUTE);
    }

    /**
     * @param string      $message
     * @param Route       $route
     * @param string      $action
     * @param string      $entityClass
     * @param string|null $associationName
     *
     * @return \LogicException
     */
    private static function createActionTypeException(
        string $message,
        Route $route,
        string $action,
        string $entityClass,
        string $associationName = null
    ): \LogicException {
        $message .= \sprintf(' Entity Class: %s. Action: %s.', $entityClass, $action);
        if ($associationName) {
            $message .= \sprintf(' Association: %s.', $associationName);
        }
        $message .= \sprintf(
            ' Route Path: %s. Controller: %s. Use "%s" route option to explicitly set the action type.',
            $route->getPath(),
            self::getController($route),
            self::ACTION_TYPE_ATTRIBUTE
        );

        return new \LogicException($message);
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    private static function endsWith(string $haystack, string $needle): bool
    {
        return \substr($haystack, -\strlen($needle)) === $needle;
    }
}
