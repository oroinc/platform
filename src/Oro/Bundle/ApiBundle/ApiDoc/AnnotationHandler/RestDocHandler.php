<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\ApiDoc\RestRouteOptionsResolver;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

/**
 * Fills ApiDoc annotation based on the configuration of API resource.
 */
class RestDocHandler implements HandlerInterface
{
    private const ID_PLACEHOLDER = '{id}';

    /** @var string The group of routes that should be processed by this handler */
    private string $routeGroup;
    private RestDocViewDetector $docViewDetector;
    private RestDocContextProvider $contextProvider;
    private ValueNormalizer $valueNormalizer;
    private RestDocIdentifierHandler $identifierHandler;
    private RestDocFiltersHandler $filtersHandler;
    private RestDocStatusCodesHandler $statusCodesHandler;

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
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method): void
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

            if (ApiAction::UPDATE_LIST === $action) {
                $context = $this->contextProvider->getContext($action, AsyncOperation::class, null, $route);
                $this->setOutput(
                    $annotation,
                    $action,
                    $this->contextProvider->getConfig($context),
                    $this->contextProvider->getMetadata($context)
                );
            } else {
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
    }

    /**
     * Checks if a route has the given placeholder in a path.
     */
    private function hasAttribute(Route $route, string $placeholder): bool
    {
        return str_contains($route->getPath(), $placeholder);
    }

    private function getEntityClass(string $entityType): string
    {
        return ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->docViewDetector->getRequestType()
        );
    }

    private function handleIdentifier(
        ApiDoc $annotation,
        Route $route,
        Context $context,
        EntityDefinitionConfig $config,
        EntityMetadata $metadata,
        ?string $associationName
    ): void {
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

    private function setDescription(ApiDoc $annotation, EntityDefinitionConfig $config): void
    {
        $description = $config->getDescription();
        if ($description) {
            $annotation->setDescription($description);
        }
    }

    private function setDocumentation(ApiDoc $annotation, EntityDefinitionConfig $config): void
    {
        $documentation = $config->getDocumentation();
        if ($documentation) {
            $annotation->setDocumentation($documentation);
        }
    }

    private function setInputMetadata(
        ApiDoc $annotation,
        string $action,
        EntityDefinitionConfig $config,
        EntityMetadata $metadata
    ): void {
        if ($this->isActionWithInput($action)) {
            ApiDocAnnotationUtil::setInput(
                $annotation,
                $this->getDirectionValue($action, 'input', $config, $metadata)
            );
        }
    }

    private function setOutputMetadata(
        ApiDoc $annotation,
        string $entityClass,
        string $action,
        EntityDefinitionConfig $config,
        EntityMetadata $metadata,
        string $associationName = null
    ): void {
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

            $this->setOutput($annotation, $action, $config, $metadata);
        }
    }

    private function setOutput(
        ApiDoc $annotation,
        string $action,
        EntityDefinitionConfig $config,
        EntityMetadata $metadata
    ): void {
        ApiDocAnnotationUtil::setOutput(
            $annotation,
            $this->getDirectionValue($action, 'output', $config, $metadata)
        );
    }

    private function getDirectionValue(
        string $action,
        string $direction,
        EntityDefinitionConfig $config,
        EntityMetadata $metadata
    ): array {
        return [
            'class'   => '',
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

    private function setStatusCodes(ApiDoc $annotation, EntityDefinitionConfig $config): void
    {
        $statusCodes = $config->getStatusCodes();
        if (null !== $statusCodes) {
            $this->statusCodesHandler->handle($annotation, $statusCodes);
        }
    }

    /**
     * Returns true if the given action receives resource data.
     */
    private function isActionWithInput(string $action): bool
    {
        return
            ApiAction::CREATE === $action
            || ApiAction::UPDATE === $action
            || ApiAction::UPDATE_SUBRESOURCE === $action
            || ApiAction::ADD_SUBRESOURCE === $action
            || ApiAction::DELETE_SUBRESOURCE === $action
            || ApiAction::UPDATE_RELATIONSHIP === $action
            || ApiAction::ADD_RELATIONSHIP === $action
            || ApiAction::DELETE_RELATIONSHIP === $action;
    }

    /**
     * Returns true if the given ApiDoc annotation has at least one success status code
     * indicates that the resource data should be returned in the response.
     */
    private function isActionWithOutput(string $action, ApiDoc $annotation): bool
    {
        $result = false;
        if (ApiAction::OPTIONS !== $action) {
            $statusCodes = ApiDocAnnotationUtil::getStatusCodes($annotation);
            foreach ($statusCodes as $statusCode => $description) {
                if ($this->isSuccessStatusCodeWithContent($statusCode)) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    private function isSuccessStatusCodeWithContent(int $statusCode): bool
    {
        return
            Response::HTTP_OK === $statusCode
            || Response::HTTP_CREATED === $statusCode
            || Response::HTTP_ACCEPTED === $statusCode;
    }

    /**
     * Returns action name that should be used to format output data.
     */
    private function getOutputAction(string $action): string
    {
        if (ApiAction::CREATE === $action || ApiAction::UPDATE === $action) {
            return ApiAction::GET;
        }

        return $action;
    }
}
