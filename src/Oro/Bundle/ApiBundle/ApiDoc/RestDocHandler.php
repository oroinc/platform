<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

/**
 * Populates ApiDoc annotation based on the configuration of Data API resource.
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

    /** @var RestDocContextProvider */
    private $contextProvider;

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var RestDocIdentifierHandler */
    private $identifierHandler;

    /** @var RestDocFiltersHandler */
    private $filtersHandler;

    /** @var RestDocStatusCodesHandler */
    private $statusCodesHandler;

    /**
     * @param string                    $routeGroup
     * @param RestDocViewDetector       $docViewDetector
     * @param RestDocContextProvider    $contextProvider
     * @param ValueNormalizer           $valueNormalizer
     * @param RestDocIdentifierHandler  $identifierHandler
     * @param RestDocFiltersHandler     $filtersHandler
     * @param RestDocStatusCodesHandler $statusCodesHandler
     */
    public function __construct(
        string $routeGroup,
        RestDocViewDetector $docViewDetector,
        RestDocContextProvider $contextProvider,
        ValueNormalizer $valueNormalizer,
        RestDocIdentifierHandler $identifierHandler,
        RestDocFiltersHandler $filtersHandler,
        RestDocStatusCodesHandler $statusCodesHandler
    ) {
        $this->routeGroup = $routeGroup;
        $this->docViewDetector = $docViewDetector;
        $this->contextProvider = $contextProvider;
        $this->valueNormalizer = $valueNormalizer;
        $this->identifierHandler = $identifierHandler;
        $this->filtersHandler = $filtersHandler;
        $this->statusCodesHandler = $statusCodesHandler;
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
        $context = $this->contextProvider->getContext($action, $entityClass, $associationName, $route);

        $config = $context->getConfig();
        if (null !== $config) {
            $this->setDescription($annotation, $config);
            $this->setDocumentation($annotation, $config);
            $this->setStatusCodes($annotation, $config);

            $metadata = $context->getMetadata();
            if (null !== $metadata) {
                if ($this->hasAttribute($route, self::ID_PLACEHOLDER)) {
                    $this->handleIdentifier($annotation, $route, $context, $config, $metadata, $associationName);
                }
                $this->filtersHandler->handle($annotation, $context->getFilters(), $metadata);
                $this->setInputMetadata($annotation, $action, $config, $metadata);
                $this->setOutputMetadata($annotation, $entityClass, $action, $config, $metadata, $associationName);
            }
        }
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
     * @param ApiDoc                 $annotation
     * @param Route                  $route
     * @param Context                $context
     * @param EntityDefinitionConfig $config
     * @param EntityMetadata         $metadata
     * @param string|null            $associationName
     */
    private function handleIdentifier(
        ApiDoc $annotation,
        Route $route,
        Context $context,
        EntityDefinitionConfig $config,
        EntityMetadata $metadata,
        ?string $associationName
    ) {
        $entityConfig = $config;
        $entityMetadata = $metadata;
        if ($associationName && $context instanceof SubresourceContext) {
            $entityConfig = $context->getParentConfig();
            $entityMetadata = $context->getParentMetadata();
        }
        if (null !== $entityConfig && null !== $entityMetadata) {
            $this->identifierHandler->handle(
                $annotation,
                $route,
                $entityMetadata,
                $entityConfig->getIdentifierDescription()
            );
        }
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
            ApiDocAnnotationUtil::setInput(
                $annotation,
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
        if ($this->isActionWithOutput($action, $annotation)) {
            if ($metadata->hasIdentifierFields()) {
                // check if output format should be taken from another action type. In this case
                // entity metadata and config will be taken for the action, those format should be used
                $substituteAction = $this->getOutputAction($action);
                if ($action !== $substituteAction) {
                    $substituteContext = $this->contextProvider->getContext(
                        $substituteAction,
                        $entityClass,
                        $associationName
                    );
                    $config = $this->contextProvider->getConfig($substituteContext);
                    $metadata = $this->contextProvider->getMetadata($substituteContext);
                }
            }

            ApiDocAnnotationUtil::setOutput(
                $annotation,
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
     * @param ApiDoc                 $annotation
     * @param EntityDefinitionConfig $config
     */
    private function setStatusCodes(ApiDoc $annotation, EntityDefinitionConfig $config)
    {
        $statusCodes = $config->getStatusCodes();
        if (null !== $statusCodes) {
            $this->statusCodesHandler->handle($annotation, $statusCodes);
        }
    }

    /**
     * Returns true if the given action receives resource data.
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
     * Returns true if the given ApiDoc annotation has at least one success status code
     * indicates that the resource data should be returned in the response.
     *
     * @param string $action
     * @param ApiDoc $annotation
     *
     * @return bool
     */
    private function isActionWithOutput($action, ApiDoc $annotation)
    {
        $result = false;
        if (ApiActions::OPTIONS !== $action) {
            $statusCodes = ApiDocAnnotationUtil::getStatusCodes($annotation);
            foreach ($statusCodes as $statusCode => $description) {
                if (\in_array($statusCode, self::SUCCESS_STATUS_CODES_WITH_CONTENT, true)) {
                    $result = true;
                    break;
                }
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
