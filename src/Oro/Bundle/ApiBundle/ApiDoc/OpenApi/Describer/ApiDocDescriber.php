<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\ApiDocAnnotationUtil;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ErrorResponseStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ErrorResponseStorageAwareTrait;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\HeaderStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\HeaderStorageAwareTrait;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ModelNameUtil;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ModelStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ModelStorageAwareTrait;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ParameterStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ParameterStorageAwareTrait;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\RequestBodyStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\RequestBodyStorageAwareTrait;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ResponseStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ResponseStorageAwareTrait;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Describes objects retrieved by ApiDocExtractor.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ApiDocDescriber implements
    DescriberInterface,
    ModelStorageAwareInterface,
    ParameterStorageAwareInterface,
    HeaderStorageAwareInterface,
    RequestBodyStorageAwareInterface,
    ResponseStorageAwareInterface,
    ErrorResponseStorageAwareInterface
{
    use ModelStorageAwareTrait;
    use ParameterStorageAwareTrait;
    use HeaderStorageAwareTrait;
    use RequestBodyStorageAwareTrait;
    use ResponseStorageAwareTrait;
    use ErrorResponseStorageAwareTrait;

    private ApiDocExtractor $extractor;
    private string $apiPrefix;
    private int $apiPrefixLength;
    private string $mediaType;
    private RequestHeaderProviderInterface $requestHeaderProvider;
    private ResponseHeaderProviderInterface $responseHeaderProvider;
    private ModelNormalizerInterface $modelNormalizer;
    private DataTypeDescribeHelper $dataTypeDescribeHelper;
    private ResourceInfoProviderInterface $resourceInfoProvider;
    private RestDocViewDetector $docViewDetector;

    public function __construct(
        ApiDocExtractor $extractor,
        string $apiPrefix,
        string $mediaType,
        RequestHeaderProviderInterface $requestHeaderProvider,
        ResponseHeaderProviderInterface $responseHeaderProvider,
        ModelNormalizerInterface $modelNormalizer,
        DataTypeDescribeHelper $dataTypeDescribeHelper,
        ResourceInfoProviderInterface $resourceInfoProvider,
        RestDocViewDetector $docViewDetector
    ) {
        $this->extractor = $extractor;
        $this->apiPrefix = $apiPrefix;
        $this->apiPrefixLength = \strlen($apiPrefix);
        $this->mediaType = $mediaType;
        $this->requestHeaderProvider = $requestHeaderProvider;
        $this->responseHeaderProvider = $responseHeaderProvider;
        $this->modelNormalizer = $modelNormalizer;
        $this->dataTypeDescribeHelper = $dataTypeDescribeHelper;
        $this->resourceInfoProvider = $resourceInfoProvider;
        $this->docViewDetector = $docViewDetector;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    #[\Override]
    public function describe(OA\OpenApi $api, array $options): void
    {
        $groupedItems = [];
        $changeEntityModels = [];
        $entities = $options['entities'] ?? null;
        $collection = $this->extractor->all($this->docViewDetector->getView());
        foreach ($collection as $item) {
            /** @var ApiDoc $apiDoc */
            $apiDoc = $item['annotation'];
            if (!$apiDoc->isResource()) {
                continue;
            }
            $route = $apiDoc->getRoute();
            $action = $this->getAction($route);
            if (!$action) {
                continue;
            }
            $entityType = $route->getDefault('entity') ?: $apiDoc->getSection();
            if (!$entityType || !(null === $entities || \in_array($entityType, $entities, true))) {
                continue;
            }
            $httpMethods = $this->getSupportedHttpMethods($route);
            if (!$httpMethods) {
                continue;
            }
            $normalizedPath = $this->normalizePath($route->getPath());
            if (!isset($groupedItems[$normalizedPath])) {
                $groupedItems[$normalizedPath] = [[], null, $item['resource']];
            }
            $groupedItems[$normalizedPath][0][] = [$apiDoc, $httpMethods];
            if (ApiAction::UPDATE_LIST === $action) {
                $groupedItems[$normalizedPath][1] = $entityType;
            } elseif (ApiAction::CREATE === $action || ApiAction::UPDATE === $action) {
                $changeEntityModels[$entityType][$action] = $apiDoc->getParameters();
            }

            $this->registerPrimaryModels($api, $apiDoc, $action, $entityType);
        }

        if ($groupedItems) {
            if (Generator::isDefault($api->paths)) {
                $api->paths = [];
            }
            foreach ($groupedItems as $normalizedPath => [$items, $updateListEntityType, $resourceId]) {
                $updateListModels = null;
                if ($updateListEntityType) {
                    $updateListModels = $changeEntityModels[$updateListEntityType] ?? null;
                }
                $this->registerPath($api, $items, $normalizedPath, $resourceId, $updateListModels);
            }
        }
    }

    private function registerPrimaryModels(OA\OpenApi $api, ApiDoc $apiDoc, string $action, string $entityType): void
    {
        if (
            ApiAction::OPTIONS === $action
            || ApiAction::DELETE === $action
            || ApiAction::DELETE_LIST === $action
            || $this->isSubresourceApiAction($action)
        ) {
            return;
        }

        $this->registerPrimaryResponseModel($api, $apiDoc, $action, $entityType);
        $this->registerPrimaryRequestModel($api, $apiDoc, $action, $entityType);
    }

    private function registerPrimaryResponseModel(
        OA\OpenApi $api,
        ApiDoc $apiDoc,
        string $action,
        string $entityType
    ): void {
        if (!$apiDoc->getResponseMap()) {
            return;
        }

        $statusCodes = ApiDocAnnotationUtil::getStatusCodes($apiDoc);
        foreach ($statusCodes as $statusCode => $item) {
            if ($statusCode >= 200 && $statusCode < 300) {
                $model = ApiDocAnnotationUtil::getResponse($apiDoc);
                if ($model) {
                    $this->registerResponseModel($api, $model, $entityType, null, $action);
                }
            }
        }
    }

    private function registerPrimaryRequestModel(
        OA\OpenApi $api,
        ApiDoc $apiDoc,
        string $action,
        string $entityType
    ): void {
        $model = $apiDoc->getParameters();
        if ($model) {
            $this->registerRequestBodyModel($api, $model, $entityType, null, $action);
        }
    }

    private function registerPath(
        OA\OpenApi $api,
        array $items,
        string $normalizedPath,
        string $resourceId,
        ?array $updateListModels
    ): void {
        $path = Util::createChildItem(OA\PathItem::class, $api, $normalizedPath);
        $path->path = $normalizedPath;
        /** @noinspection UnsupportedStringOffsetOperationsInspection */
        $api->paths[] = $path;
        /** @var ApiDoc $apiDoc */
        foreach ($items as [$apiDoc, $httpMethods]) {
            $route = $apiDoc->getRoute();
            $action = $this->getAction($route);
            $entityType = $route->getDefault('entity') ?: $apiDoc->getSection();
            $associationName = $this->getAssociationName($action, $route);
            $sectionName = $apiDoc->getSection();

            foreach ($httpMethods as $httpMethod) {
                $operation = $this->createOperation($path, $httpMethod);
                $operation->operationId = $this->getOperationId($resourceId, $httpMethod);
                $operation->summary = $apiDoc->getDescription();
                $operation->description = $apiDoc->getDocumentation();
                if ($apiDoc->getDeprecated()) {
                    $operation->deprecated = true;
                }
                if ($sectionName) {
                    $operation->tags = [$sectionName];
                }

                $this->registerHeaders(
                    $api,
                    $operation,
                    $this->requestHeaderProvider->getRequestHeaders($action, $entityType, $associationName)
                );
                $this->registerHeaders($api, $operation, $apiDoc->getHeaders());
                $this->registerRequirements($api, $operation, $apiDoc->getRequirements());
                $this->registerFilters($api, $operation, $apiDoc->getFilters());
                $this->registerResponses($api, $operation, $apiDoc, $entityType, $associationName, $action);
                if (ApiAction::UPDATE_LIST === $action) {
                    $this->registerUpdateListRequestBody($api, $operation, $entityType, $updateListModels);
                } else {
                    $this->registerRequestBody($api, $operation, $apiDoc, $entityType, $associationName, $action);
                }
            }
        }
    }

    private function registerHeaders(OA\OpenApi $api, OA\Operation $operation, array $headers): void
    {
        if (!$headers) {
            return;
        }

        if (Generator::isDefault($operation->parameters)) {
            $operation->parameters = [];
        }

        foreach ($headers as $name => $item) {
            /** @noinspection UnsupportedStringOffsetOperationsInspection */
            $operation->parameters[] = Util::createParameterRef(
                $operation,
                $this->parameterStorage->registerParameter($api, OA\HeaderParameter::class, $name, $item)->parameter
            );
        }
    }

    private function registerRequirements(OA\OpenApi $api, OA\Operation $operation, array $requirements): void
    {
        if (!$requirements) {
            return;
        }

        if (Generator::isDefault($operation->parameters)) {
            $operation->parameters = [];
        }

        foreach ($requirements as $name => $item) {
            if (\array_key_exists('dataType', $item)) {
                $item['type'] = $item['dataType'];
                unset($item['dataType']);
            }
            /** @noinspection UnsupportedStringOffsetOperationsInspection */
            $operation->parameters[] = Util::createParameterRef(
                $operation,
                $this->parameterStorage->registerParameter($api, OA\PathParameter::class, $name, $item)->parameter
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function registerFilters(OA\OpenApi $api, OA\Operation $operation, array $filters): void
    {
        if (!$filters) {
            return;
        }

        if (Generator::isDefault($operation->parameters)) {
            $operation->parameters = [];
        }

        foreach ($filters as $name => $item) {
            $operators = $item['operators'] ?? null;
            if (null !== $operators) {
                $operators = explode(',', $operators);
            }
            if (null === $operators) {
                $this->registerFilter($api, $operation, $name, $item);
            } else {
                foreach ($operators as $operator) {
                    $nameWithOperator = sprintf('%s[%s]', $name, $operator);
                    if (FilterOperator::EXISTS === $operator || FilterOperator::EMPTY_VALUE === $operator) {
                        $booleanItem = ['type' => Util::TYPE_BOOLEAN];
                        if (\array_key_exists('description', $item)) {
                            $booleanItem['description'] = $item['description'];
                        }
                        $this->registerFilter($api, $operation, $nameWithOperator, $booleanItem);
                    } else {
                        if (FilterOperator::EQ === $operator) {
                            $this->registerFilter($api, $operation, $name, $item);
                        }
                        $this->registerFilter($api, $operation, $nameWithOperator, $item);
                    }
                }
            }
        }
    }

    private function registerFilter(OA\OpenApi $api, OA\Operation $operation, string $name, array $item): void
    {
        /** @noinspection UnsupportedStringOffsetOperationsInspection */
        $operation->parameters[] = Util::createParameterRef(
            $operation,
            $this->parameterStorage->registerParameter($api, OA\QueryParameter::class, $name, $item)->parameter
        );
    }

    private function registerRequestBody(
        OA\OpenApi $api,
        OA\Operation $operation,
        ApiDoc $apiDoc,
        string $entityType,
        ?string $associationName,
        string $action
    ): void {
        $model = $apiDoc->getParameters();
        if (!$model) {
            return;
        }

        $operation->requestBody = Util::createRequestBodyRef(
            $operation,
            $this->requestBodyStorage->registerRequestBody(
                $api,
                $this->mediaType,
                $this->registerRequestBodyModel($api, $model, $entityType, $associationName, $action)
            )->request
        );
    }

    private function registerUpdateListRequestBody(
        OA\OpenApi $api,
        OA\Operation $operation,
        string $entityType,
        ?array $updateListModels
    ): void {
        if ($updateListModels) {
            $modelNames = [];
            foreach ($updateListModels as $action => $model) {
                $modelNames[] = $this->registerModel(
                    $api,
                    $model,
                    ModelNameUtil::buildModelName($entityType, $action),
                    $this->getTargetEntityType($entityType, $action),
                    $action
                );
            }
            $targetSchema = $this->modelStorage->registerUnionModel(
                $api,
                $modelNames,
                ModelNameUtil::buildModelName($entityType . 'UpdateList'),
                true
            )->schema;
        } else {
            $targetSchema = $this->dataTypeDescribeHelper->registerType($api, Util::TYPE_OBJECT . '[]')->schema;
        }
        $operation->requestBody = Util::createRequestBodyRef(
            $operation,
            $this->requestBodyStorage->registerRequestBody($api, $this->mediaType, $targetSchema)->request
        );
    }

    private function registerResponses(
        OA\OpenApi $api,
        OA\Operation $operation,
        ApiDoc $apiDoc,
        string $entityType,
        ?string $associationName,
        string $action
    ): void {
        $operation->responses = [];
        $responseMap = $apiDoc->getResponseMap();
        $statusCodes = ApiDocAnnotationUtil::getStatusCodes($apiDoc);
        foreach ($statusCodes as $statusCode => $item) {
            $description = $item[0] ?? null;
            if ($statusCode >= 400) {
                $this->registerErrorResponse(
                    $api,
                    $operation,
                    $statusCode,
                    $description,
                    $entityType,
                    $associationName,
                    $action
                );
            } else {
                $model = null;
                if (isset($responseMap[$statusCode]) || (201 === $statusCode && isset($statusCodes[200]))) {
                    $model = ApiDocAnnotationUtil::getResponse($apiDoc);
                }
                $this->registerResponse(
                    $api,
                    $operation,
                    $statusCode,
                    $description,
                    $model,
                    $entityType,
                    $associationName,
                    $action
                );
            }
        }
    }

    private function registerResponse(
        OA\OpenApi $api,
        OA\Operation $operation,
        int $statusCode,
        string $description,
        ?array $model,
        string $entityType,
        ?string $associationName,
        string $action
    ): void {
        /** @noinspection UnsupportedStringOffsetOperationsInspection */
        $operation->responses[] = Util::createResponseRef(
            $operation,
            $statusCode,
            $this->responseStorage->registerResponse(
                $api,
                $description,
                $this->mediaType,
                $model ? $this->registerResponseModel($api, $model, $entityType, $associationName, $action) : null,
                $this->registerResponseHeaders($api, $entityType, $associationName, $action)
            )->response
        );
    }

    private function registerErrorResponse(
        OA\OpenApi $api,
        OA\Operation $operation,
        int $statusCode,
        string $description,
        string $entityType,
        ?string $associationName,
        string $action
    ): void {
        /** @noinspection UnsupportedStringOffsetOperationsInspection */
        $operation->responses[] = Util::createResponseRef(
            $operation,
            $statusCode,
            $this->errorResponseStorage
                ->registerErrorResponse(
                    $api,
                    $statusCode,
                    $description,
                    $this->mediaType,
                    'failure',
                    $this->registerResponseHeaders($api, $entityType, $associationName, $action, true)
                )
                ->response
        );
    }

    private function registerResponseHeaders(
        OA\OpenApi $api,
        string $entityType,
        ?string $associationName,
        string $action,
        bool $isErrorResponse = false
    ): ?array {
        $headers = $this->responseHeaderProvider->getResponseHeaders(
            $action,
            $entityType,
            $associationName,
            $isErrorResponse
        );
        if (!$headers) {
            return null;
        }

        $headerNames = [];
        foreach ($headers as $name => $item) {
            $headerNames[$name] = $this->headerStorage->registerHeader($api, $name, $item)->header;
        }

        return $headerNames;
    }

    private function registerRequestBodyModel(
        OA\OpenApi $api,
        array $model,
        string $entityType,
        ?string $associationName,
        string $action
    ): string {
        $isCollection = $this->isCollectionRequest($action, $entityType, $associationName);

        return $this->registerModel(
            $api,
            $model,
            ModelNameUtil::buildModelName($entityType, $action, $isCollection, $associationName),
            $this->getTargetEntityType($entityType, $action, $associationName),
            $action,
            false,
            $isCollection
        );
    }

    private function registerResponseModel(
        OA\OpenApi $api,
        array $model,
        string $entityType,
        ?string $associationName,
        string $action
    ): string {
        $isCollection = $this->isCollectionResponse($action, $entityType, $associationName);

        return $this->registerModel(
            $api,
            $model,
            ModelNameUtil::buildModelName($entityType, $action, $isCollection, $associationName),
            $this->getTargetEntityType($entityType, $action, $associationName),
            $action,
            true,
            $isCollection
        );
    }

    private function registerModel(
        OA\OpenApi $api,
        array $model,
        string $suggestedModelName,
        ?string $entityType,
        string $action,
        bool $isResponseModel = false,
        bool $isCollection = false
    ): string {
        return $this->modelStorage->registerModel(
            $api,
            $this->modelNormalizer->normalizeModel($model, $action, $isResponseModel),
            $suggestedModelName,
            $entityType,
            $isCollection,
            true,
            $this->isRelationshipRelatedApiAction($action)
        )->schema;
    }

    private function getAction(Route $route): ?string
    {
        $action = $route->getDefault('_action');
        if ($action) {
            return $action;
        }

        // try to resolve an action for API resources with a custom controller
        $methods = $route->getMethods();
        if (\count($methods) !== 1) {
            return null;
        }
        $method = strtoupper(reset($methods));
        if (Request::METHOD_OPTIONS === $method) {
            return ApiAction::OPTIONS;
        }
        $normalizedPath = $this->normalizePath($route->getPath());
        if (str_ends_with($normalizedPath, '/{id}')) {
            return $this->getActionForItemRoute($method);
        }
        $i = strpos($normalizedPath, '/{id}/relationships/');
        if (false !== $i) {
            return !str_contains(substr($normalizedPath, $i + \strlen('/{id}/relationships/')), '/')
                ? $this->getActionForRelationshipRoute($method)
                : null;
        }
        $i = strpos($normalizedPath, '/{id}/');
        if (false !== $i) {
            return !str_contains(substr($normalizedPath, $i + \strlen('/{id}/')), '/')
                ? $this->getActionForSubresourceRoute($method)
                : null;
        }

        return $this->getActionForListRoute($method);
    }

    private function getAssociationName(string $action, Route $route): ?string
    {
        if (!$this->isSubresourceApiAction($action)) {
            return null;
        }

        $associationName = $route->getDefault('association');
        if ($associationName) {
            return $associationName;
        }

        // try to resolve an association name for API resources with a custom controller
        $normalizedPath = $this->normalizePath($route->getPath());
        $i = strpos($normalizedPath, '/{id}/relationships/');
        if (false !== $i) {
            $associationName = substr($normalizedPath, $i + \strlen('/{id}/relationships/'));

            return !str_contains($associationName, '/') ? $associationName : null;
        }
        $i = strpos($normalizedPath, '/{id}/');
        if (false !== $i) {
            $associationName = substr($normalizedPath, $i + \strlen('/{id}/'));

            return !str_contains($associationName, '/') ? $associationName : null;
        }

        return null;
    }

    private function getActionForItemRoute(string $method): ?string
    {
        if (Request::METHOD_GET === $method) {
            return ApiAction::GET;
        }
        if (Request::METHOD_PATCH === $method) {
            return ApiAction::UPDATE;
        }
        if (Request::METHOD_DELETE === $method) {
            return ApiAction::DELETE;
        }

        return null;
    }

    private function getActionForListRoute(string $method): ?string
    {
        if (Request::METHOD_GET === $method) {
            return ApiAction::GET_LIST;
        }
        if (Request::METHOD_POST === $method) {
            return ApiAction::CREATE;
        }
        if (Request::METHOD_PATCH === $method) {
            return ApiAction::UPDATE_LIST;
        }
        if (Request::METHOD_DELETE === $method) {
            return ApiAction::DELETE_LIST;
        }

        return null;
    }

    private function getActionForRelationshipRoute(string $method): ?string
    {
        if (Request::METHOD_GET === $method) {
            return ApiAction::GET_RELATIONSHIP;
        }
        if (Request::METHOD_POST === $method) {
            return ApiAction::ADD_RELATIONSHIP;
        }
        if (Request::METHOD_PATCH === $method) {
            return ApiAction::UPDATE_RELATIONSHIP;
        }
        if (Request::METHOD_DELETE === $method) {
            return ApiAction::DELETE_RELATIONSHIP;
        }

        return null;
    }

    private function getActionForSubresourceRoute(string $method): ?string
    {
        if (Request::METHOD_GET === $method) {
            return ApiAction::GET_SUBRESOURCE;
        }
        if (Request::METHOD_POST === $method) {
            return ApiAction::ADD_SUBRESOURCE;
        }
        if (Request::METHOD_PATCH === $method) {
            return ApiAction::UPDATE_SUBRESOURCE;
        }
        if (Request::METHOD_DELETE === $method) {
            return ApiAction::DELETE_SUBRESOURCE;
        }

        return null;
    }

    private function getSupportedHttpMethods(Route $route): array
    {
        return array_intersect(
            array_map('strtolower', $route->getMethods()) ?: Util::OPERATIONS,
            Util::OPERATIONS
        );
    }

    private function normalizePath(string $path): string
    {
        return str_ends_with($path, '.{_format}')
            ? substr($path, 0, -10)
            : $path;
    }

    private function createOperation(OA\PathItem $path, string $httpMethod): OA\Operation
    {
        $operation = Util::createChildItem('OpenApi\Annotations\\' . ucfirst($httpMethod), $path, $httpMethod);
        $path->{$httpMethod} = $operation;

        return $operation;
    }

    private function getOperationId(string $resourceId, string $httpMethod): string
    {
        $result = $resourceId;
        $pos = strpos($resourceId, $this->apiPrefix);
        if (false !== $pos) {
            $result = substr($result, $pos + $this->apiPrefixLength);
        }
        $result = trim(preg_replace('/[^0-9a-zA-Z]/', '-', $result), '-');
        $result = preg_replace('/-+/', '-', $result);

        return $result . '-' . $httpMethod;
    }

    private function isRelationshipRelatedApiAction(string $action): bool
    {
        return
            ApiAction::GET_RELATIONSHIP === $action
            || ApiAction::ADD_RELATIONSHIP === $action
            || ApiAction::UPDATE_RELATIONSHIP === $action
            || ApiAction::DELETE_RELATIONSHIP === $action;
    }

    private function isSubresourceRelatedApiAction(string $action): bool
    {
        return
            ApiAction::GET_SUBRESOURCE === $action
            || ApiAction::ADD_SUBRESOURCE === $action
            || ApiAction::UPDATE_SUBRESOURCE === $action
            || ApiAction::DELETE_SUBRESOURCE === $action;
    }

    private function isSubresourceApiAction(string $action): bool
    {
        return
            $this->isRelationshipRelatedApiAction($action)
            || $this->isSubresourceRelatedApiAction($action);
    }

    private function getTargetEntityType(string $entityType, string $action, ?string $associationName = null): ?string
    {
        if (!$this->isSubresourceApiAction($action)) {
            return $entityType;
        }

        return $this->resourceInfoProvider->getSubresourceTargetEntityType($entityType, $associationName);
    }

    private function isCollectionRequest(string $action, string $entityType, ?string $associationName = null): bool
    {
        if (ApiAction::OPTIONS === $action) {
            return false;
        }

        if (null === $associationName) {
            return ApiAction::UPDATE_LIST === $action;
        }

        return $this->resourceInfoProvider->isCollectionSubresource($entityType, $associationName);
    }

    private function isCollectionResponse(string $action, string $entityType, ?string $associationName = null): bool
    {
        if (ApiAction::OPTIONS === $action) {
            return false;
        }

        if (null === $associationName) {
            return ApiAction::GET_LIST === $action;
        }

        return $this->resourceInfoProvider->isCollectionSubresource($entityType, $associationName);
    }
}
