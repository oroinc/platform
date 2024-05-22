<?php

namespace Oro\Bundle\ApiBundle\Autocomplete;

use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Provides a list of all entities available for OpenAPI specification.
 */
class OpenApiSpecificationEntityProvider implements OpenApiSpecificationEntityProviderInterface
{
    private ResourcesProvider $resourcesProvider;
    private ValueNormalizer $valueNormalizer;
    private EntityNameProvider $entityNameProvider;
    private RestDocViewDetector $docViewDetector;

    public function __construct(
        ResourcesProvider $resourcesProvider,
        ValueNormalizer $valueNormalizer,
        EntityNameProvider $entityNameProvider,
        RestDocViewDetector $docViewDetector
    ) {
        $this->resourcesProvider = $resourcesProvider;
        $this->valueNormalizer = $valueNormalizer;
        $this->entityNameProvider = $entityNameProvider;
        $this->docViewDetector = $docViewDetector;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntities(string $view): array
    {
        $previousView = $this->docViewDetector->getView();
        $this->docViewDetector->setView($view);
        try {
            $version = $this->docViewDetector->getVersion();
            $requestType = $this->docViewDetector->getRequestType();
        } finally {
            $this->docViewDetector->setView($previousView);
        }

        $entities = [];
        $entityClasses = $this->resourcesProvider->getAccessibleResources($version, $requestType);
        foreach ($entityClasses as $entityClass) {
            $entities[] = new OpenApiSpecificationEntity(
                ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $entityClass, $requestType),
                $this->entityNameProvider->getEntityName($entityClass)
            );
        }
        usort($entities, function (OpenApiSpecificationEntity $item1, OpenApiSpecificationEntity $item2): int {
            return strcmp($item1->getName(), $item2->getName());
        });

        return $entities;
    }
}
