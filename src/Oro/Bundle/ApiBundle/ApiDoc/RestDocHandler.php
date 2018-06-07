<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

/**
 * Populates ApiDoc annotation based on the confoguration of Data API resource.
 */
class RestDocHandler implements HandlerInterface
{
    private const ID_PLACEHOLDER = '{id}';

    private const SUCCESS_STATUS_CODES_WITH_CONTENT = [
        Response::HTTP_OK,
        Response::HTTP_CREATED,
        Response::HTTP_ACCEPTED
    ];

    /** @var string The group of routes that should be processed by this handler */
    private $routeGroup;

    /** @var RestDocViewDetector */
    private $docViewDetector;

    /** @var ActionProcessorBagInterface */
    private $processorBag;

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var RestDocIdentifierHandler */
    private $identifierHandler;

    /** @var RestDocFiltersHandler */
    private $filtersHandler;

    /**
     * @param string                      $routeGroup
     * @param RestDocViewDetector         $docViewDetector
     * @param ActionProcessorBagInterface $processorBag
     * @param ValueNormalizer             $valueNormalizer
     * @param RestDocIdentifierHandler    $identifierHandler
     * @param RestDocFiltersHandler       $filtersHandler
     */
    public function __construct(
        string $routeGroup,
        RestDocViewDetector $docViewDetector,
        ActionProcessorBagInterface $processorBag,
        ValueNormalizer $valueNormalizer,
        RestDocIdentifierHandler $identifierHandler,
        RestDocFiltersHandler $filtersHandler
    ) {
        $this->routeGroup = $routeGroup;
        $this->docViewDetector = $docViewDetector;
        $this->processorBag = $processorBag;
        $this->valueNormalizer = $valueNormalizer;
        $this->identifierHandler = $identifierHandler;
        $this->filtersHandler = $filtersHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        if ($route->getOption(RestRouteOptionsResolver::GROUP_OPTION) !== $this->routeGroup
            || $this->docViewDetector->getRequestType()->isEmpty()
        ) {
            return;
        }
        $action = $route->getDefault(RestRouteOptionsResolver::ACTION_ATTRIBUTE);
        if (!$action) {
            return;
        }
        $entityType = $route->getDefault(RestRouteOptionsResolver::ENTITY_ATTRIBUTE);
        if (!$entityType) {
            return;
        }

        $annotation->setSection($entityType);
        $entityClass = $this->getEntityClass($entityType);
        $associationName = $route->getDefault(RestRouteOptionsResolver::ASSOCIATION_ATTRIBUTE);
        $context = $this->getContext($action, $entityClass, $associationName);

        $config = $context->getConfig();
        if (null === $config) {
            return;
        }

        $this->setDescription($annotation, $config);
        $this->setDocumentation($annotation, $config);
        $this->setStatusCodes($annotation, $config);

        $metadata = $context->getMetadata();
        if (null === $metadata) {
            return;
        }

        if ($this->hasAttribute($route, self::ID_PLACEHOLDER)) {
            $entityConfig = $config;
            $entityMetadata = $metadata;
            if ($associationName) {
                $entityConfig = $context->getParentConfig();
                $entityMetadata = $context->getParentMetadata();
            }
            $this->identifierHandler->handle(
                $annotation,
                $route,
                $entityMetadata,
                $entityConfig->getIdentifierDescription()
            );
        }

        $this->filtersHandler->handle($annotation, $context->getFilters(), $metadata);
        $this->setInputMetadata($annotation, $action, $config, $metadata);
        $this->setOutputMetadata($annotation, $entityClass, $action, $config, $metadata, $associationName);
    }

    /**
     * Checks if a route has the given placeholder in a path.
     *
     * @param Route  $route
     * @param string $placeholder
     *
     * @return bool
     */
    private function hasAttribute(Route $route, $placeholder)
    {
        return false !== \strpos($route->getPath(), $placeholder);
    }

    /**
     * @param string $entityType
     *
     * @return string
     */
    private function getEntityClass($entityType)
    {
        return ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->docViewDetector->getRequestType()
        );
    }

    /**
     * @param string      $action
     * @param string      $entityClass
     * @param string|null $associationName
     *
     * @return Context|SubresourceContext
     */
    private function getContext($action, $entityClass, $associationName = null)
    {
        $processor = $this->processorBag->getProcessor($action);
        /** @var Context $context */
        $context = $processor->createContext();
        $context->addConfigExtra(new DescriptionsConfigExtra());
        $context->getRequestType()->set($this->docViewDetector->getRequestType());
        $context->setVersion($this->docViewDetector->getVersion());
        $context->setLastGroup('initialize');
        if ($associationName) {
            /** @var SubresourceContext $context */
            $context->setParentClassName($entityClass);
            $context->setAssociationName($associationName);
            $parentConfigExtras = $context->getParentConfigExtras();
            $parentConfigExtras[] = new DescriptionsConfigExtra();
            $context->setParentConfigExtras($parentConfigExtras);
        } else {
            $context->setClassName($entityClass);
        }

        $processor->process($context);

        return $context;
    }

    /**
     * @param Context $context
     *
     * @return EntityDefinitionConfig
     */
    private function getConfig(Context $context): EntityDefinitionConfig
    {
        $config = $context->getConfig();
        if (null === $config) {
            $message = \sprintf(
                'The configuration for "%s" cannot be loaded. Action: %s',
                $context->getClassName(),
                $context->getAction()
            );
            if ($context instanceof SubresourceContext) {
                $message .= \sprintf(' Association: %s.', $context->getAssociationName());
            }
            throw new \LogicException($message);
        }

        return $config;
    }

    /**
     * @param Context $context
     *
     * @return EntityMetadata
     */
    private function getMetadata(Context $context): EntityMetadata
    {
        $metadata = $context->getMetadata();
        if (null === $metadata) {
            $message = \sprintf(
                'The metadata for "%s" cannot be loaded. Action: %s',
                $context->getClassName(),
                $context->getAction()
            );
            if ($context instanceof SubresourceContext) {
                $message .= \sprintf(' Association: %s.', $context->getAssociationName());
            }
            throw new \LogicException($message);
        }

        return $metadata;
    }

    /**
     * @param ApiDoc                 $annotation
     * @param EntityDefinitionConfig $config
     */
    private function setDescription(ApiDoc $annotation, EntityDefinitionConfig $config)
    {
        $description = $config->getDescription();
        if ($description) {
            $annotation->setDescription($description);
        }
    }

    /**
     * @param ApiDoc                 $annotation
     * @param EntityDefinitionConfig $config
     */
    private function setDocumentation(ApiDoc $annotation, EntityDefinitionConfig $config)
    {
        $documentation = $config->getDocumentation();
        if ($documentation) {
            $annotation->setDocumentation($documentation);
        }
    }

    /**
     * @param ApiDoc                 $annotation
     * @param string                 $action
     * @param EntityDefinitionConfig $config
     * @param EntityMetadata         $metadata
     */
    private function setInputMetadata(
        ApiDoc $annotation,
        $action,
        EntityDefinitionConfig $config,
        EntityMetadata $metadata
    ) {
        if ($this->isActionWithInput($action)) {
            $this->setDirectionValue(
                $annotation,
                'input',
                $this->getDirectionValue($action, 'input', $config, $metadata)
            );
        }
    }

    /**
     * @param ApiDoc                 $annotation
     * @param string                 $entityClass
     * @param string                 $action
     * @param EntityDefinitionConfig $config
     * @param EntityMetadata         $metadata
     * @param string|null            $associationName
     */
    private function setOutputMetadata(
        ApiDoc $annotation,
        $entityClass,
        $action,
        EntityDefinitionConfig $config,
        EntityMetadata $metadata,
        $associationName = null
    ) {
        if ($this->isActionWithOutput($annotation)) {
            if ($metadata->hasIdentifierFields()) {
                // check if output format should be taken from another action type. In this case
                // entity metadata and config will be taken for the action, those format should be used
                $substituteAction = $this->getOutputAction($action);
                if ($action !== $substituteAction) {
                    $substituteContext = $this->getContext($substituteAction, $entityClass, $associationName);
                    $config = $this->getConfig($substituteContext);
                    $metadata = $this->getMetadata($substituteContext);
                }
            }

            $this->setDirectionValue(
                $annotation,
                'output',
                $this->getDirectionValue($action, 'output', $config, $metadata)
            );
        }
    }

    /**
     * @param string                 $action
     * @param string                 $direction
     * @param EntityDefinitionConfig $config
     * @param EntityMetadata         $metadata
     *
     * @return array
     */
    private function getDirectionValue($action, $direction, EntityDefinitionConfig $config, EntityMetadata $metadata)
    {
        return [
            'class'   => null,
            'options' => [
                'direction' => $direction,
                'metadata'  => new ApiDocMetadata(
                    $action,
                    $metadata,
                    $config,
                    $this->docViewDetector->getRequestType()
                )
            ]
        ];
    }

    /**
     * @param ApiDoc $annotation
     * @param string $direction
     * @param array  $value
     */
    private function setDirectionValue(ApiDoc $annotation, $direction, array $value)
    {
        // unfortunately there is no other way to update "input" and "output" parameters
        // except to use the reflection
        $outputProperty = ReflectionUtil::getProperty(new \ReflectionClass($annotation), $direction);
        $outputProperty->setAccessible(true);
        $outputProperty->setValue($annotation, $value);
    }

    /**
     * @param ApiDoc $annotation
     * @return array [status code => description, ...]
     */
    private function getStatusCodes(ApiDoc $annotation)
    {
        // unfortunately there is no other way to get "statusCodes" property
        // except to use the reflection
        $statusCodesProperty = ReflectionUtil::getProperty(new \ReflectionClass($annotation), 'statusCodes');
        $statusCodesProperty->setAccessible(true);

        return $statusCodesProperty->getValue($annotation);
    }

    /**
     * @param ApiDoc                 $annotation
     * @param EntityDefinitionConfig $config
     */
    private function setStatusCodes(ApiDoc $annotation, EntityDefinitionConfig $config)
    {
        $statusCodes = $config->getStatusCodes();
        if (null !== $statusCodes) {
            $codes = $statusCodes->getCodes();
            \ksort($codes);
            foreach ($codes as $statusCode => $code) {
                if (!$code->isExcluded()) {
                    $annotation->addStatusCode($statusCode, $code->getDescription());
                }
            }
        }
    }

    /**
     * Returns true in case if the given action receives resource data.
     *
     * @param string $action
     *
     * @return bool
     */
    private function isActionWithInput($action)
    {
        return \in_array(
            $action,
            [
                ApiActions::CREATE,
                ApiActions::UPDATE,
                ApiActions::UPDATE_SUBRESOURCE,
                ApiActions::ADD_SUBRESOURCE,
                ApiActions::DELETE_SUBRESOURCE,
                ApiActions::UPDATE_RELATIONSHIP,
                ApiActions::ADD_RELATIONSHIP,
                ApiActions::DELETE_RELATIONSHIP
            ],
            true
        );
    }

    /**
     * Returns true in case if the given ApiDoc annotation has at least one sucsess status code
     * indicates that the resource data should be returned in the response.
     *
     * @param ApiDoc $annotation
     *
     * @return bool
     */
    private function isActionWithOutput(ApiDoc $annotation)
    {
        $result = false;
        $statusCodes = $this->getStatusCodes($annotation);
        foreach ($statusCodes as $statusCode => $description) {
            if (\in_array($statusCode, self::SUCCESS_STATUS_CODES_WITH_CONTENT, true)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Returns action name that should be used to format output data.
     *
     * @param string $action
     *
     * @return string
     */
    private function getOutputAction($action)
    {
        if (\in_array($action, [ApiActions::CREATE, ApiActions::UPDATE], true)) {
            return ApiActions::GET;
        }

        return $action;
    }
}
