<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides a set of methods to get information about API resources.
 */
class ResourceInfoProvider implements ResourceInfoProviderInterface, ResetInterface
{
    private ResourcesProvider $resourcesProvider;
    private SubresourcesProvider $subresourcesProvider;
    private RestDocViewDetector $docViewDetector;
    private ValueNormalizer $valueNormalizer;
    private array $untypedEntityType = [];
    private array $resourcesWithoutIdentifier = [];
    private array $subresources = [];

    public function __construct(
        ResourcesProvider $resourcesProvider,
        SubresourcesProvider $subresourcesProvider,
        RestDocViewDetector $docViewDetector,
        ValueNormalizer $valueNormalizer
    ) {
        $this->resourcesProvider = $resourcesProvider;
        $this->subresourcesProvider = $subresourcesProvider;
        $this->docViewDetector = $docViewDetector;
        $this->valueNormalizer = $valueNormalizer;
    }

    #[\Override]
    public function reset(): void
    {
        $this->untypedEntityType = [];
        $this->resourcesWithoutIdentifier = [];
        $this->subresources = [];
    }

    #[\Override]
    public function getEntityClass(string $entityType): ?string
    {
        return ValueNormalizerUtil::tryConvertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->docViewDetector->getRequestType()
        );
    }

    #[\Override]
    public function getEntityType(string $entityClass): ?string
    {
        return ValueNormalizerUtil::tryConvertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $this->docViewDetector->getRequestType()
        );
    }

    #[\Override]
    public function isUntypedEntityType(string $entityType): bool
    {
        $view = $this->docViewDetector->getView();
        if (!isset($this->untypedEntityType[$view])) {
            $this->untypedEntityType[$view] = $this->getEntityType(EntityIdentifier::class);
        }

        return $entityType === $this->untypedEntityType[$view];
    }

    #[\Override]
    public function isResourceWithoutIdentifier(string $entityType): bool
    {
        $view = $this->docViewDetector->getView();
        if (!isset($this->resourcesWithoutIdentifier[$view])) {
            $this->resourcesWithoutIdentifier[$view] = $this->loadResourcesWithoutIdentifier(
                $this->docViewDetector->getRequestType()
            );
        }

        return isset($this->resourcesWithoutIdentifier[$view][$entityType]);
    }

    #[\Override]
    public function isCollectionSubresource(string $entityType, string $associationName): bool
    {
        $subresource = $this->getSubresource($entityType, $associationName);

        return null !== $subresource && $subresource->isCollection();
    }

    #[\Override]
    public function getSubresourceTargetEntityType(string $entityType, string $associationName): ?string
    {
        $targetEntityClass = $this->getSubresource($entityType, $associationName)?->getTargetClassName();

        return $targetEntityClass && EntityIdentifier::class !== $targetEntityClass
            ? $this->getEntityType($targetEntityClass)
            : null;
    }

    private function getSubresource(string $entityType, string $associationName): ?ApiSubresource
    {
        $view = $this->docViewDetector->getView();
        if (!isset($this->subresources[$entityType])
            || !\array_key_exists($view, $this->subresources[$entityType])
        ) {
            $this->subresources[$entityType][$view] = $this->loadSubresources(
                $entityType,
                $this->docViewDetector->getVersion(),
                $this->docViewDetector->getRequestType()
            );
        }

        return $this->subresources[$entityType][$view]?->getSubresource($associationName);
    }

    private function loadResourcesWithoutIdentifier(RequestType $requestType): array
    {
        $resourcesWithoutIdentifier = [];
        $version = $this->docViewDetector->getVersion();
        $allResources = $this->resourcesProvider->getResources($version, $requestType);
        foreach ($allResources as $resource) {
            $entityClass = $resource->getEntityClass();
            if (!$this->resourcesProvider->isResourceWithoutIdentifier($entityClass, $version, $requestType)) {
                continue;
            }
            $entityType = ValueNormalizerUtil::tryConvertToEntityType(
                $this->valueNormalizer,
                $entityClass,
                $requestType
            );
            if (!$entityType) {
                continue;
            }
            $resourcesWithoutIdentifier[$entityType] = true;
        }

        return $resourcesWithoutIdentifier;
    }

    private function loadSubresources(
        string $entityType,
        string $version,
        RequestType $requestType
    ): ?ApiResourceSubresources {
        $entityClass = ValueNormalizerUtil::tryConvertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $requestType
        );
        if (!$entityClass) {
            return null;
        }

        return $this->subresourcesProvider->getSubresources($entityClass, $version, $requestType);
    }
}
