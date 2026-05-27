<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Builds a documentation about API resources that use an identifier from an external system.
 */
class ExtIdEntitiesDocumentationProvider implements DocumentationProviderInterface
{
    private array $extIdEntityDescriptions = [];

    public function __construct(
        private readonly array $extIdEntities,
        private readonly ValueNormalizer $valueNormalizer
    ) {
    }

    public function setExtIdEntityDescription(string $entityClass, string $description): void
    {
        $this->extIdEntityDescriptions[$entityClass] = $description;
    }

    #[\Override]
    public function getDocumentation(RequestType $requestType): ?string
    {
        $apiResources = $this->getExtIdApiResources($requestType);
        if (empty($apiResources)) {
            return null;
        }

        $items = [];
        foreach ($apiResources as $apiResource) {
            $items[] = '- ' . $apiResource;
        }

        return \sprintf(
            "\nResources with external IDs enabled:\n\n%s",
            implode("\n", $items)
        );
    }

    private function getExtIdApiResources(RequestType $requestType): array
    {
        $resources = [];
        foreach ($this->extIdEntities as $entityClass => $idFieldName) {
            $resource = ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $entityClass, $requestType);
            if (!empty($this->extIdEntityDescriptions[$entityClass])) {
                $resource .= ' ' . $this->extIdEntityDescriptions[$entityClass];
            }
            $resources[] = $resource;
        }

        sort($resources);

        return $resources;
    }
}
