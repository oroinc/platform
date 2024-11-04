<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Generator;

use OpenApi\Annotations as OA;
use OpenApi\Generator;

/**
 * Overrides ref() to improve its performance.
 */
class OpenApi extends OA\OpenApi
{
    private ?int $componentsPrefixLength = null;
    private ?array $collectionIdentifiers = null;
    private array $collectionExistence = [];
    private array $itemExistence = [];

    #[\Override]
    public function ref(string $ref)
    {
        if (!str_starts_with($ref, OA\Components::COMPONENTS_PREFIX)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported $ref "%s", it should start with "%s"',
                $ref,
                OA\Components::COMPONENTS_PREFIX
            ));
        }

        $path = $this->getRefPath($ref);
        $delimiterPos = strpos($path, '/');
        if (false === $delimiterPos) {
            throw $this->createRefNotFoundException($ref);
        }

        $collectionName = substr($path, 0, $delimiterPos);
        if (!\array_key_exists($collectionName, $this->collectionExistence)) {
            $this->collectionExistence[$collectionName] = property_exists(OA\Components::class, $collectionName);
        }
        if (!$this->collectionExistence[$collectionName]) {
            throw $this->createRefNotFoundException($ref);
        }

        if (!isset($this->itemExistence[$collectionName])) {
            $idPropertyName = $this->getCollectionIdentifierPropertyName($collectionName);
            if (null === $idPropertyName) {
                throw $this->createRefNotFoundException($ref);
            }
            $this->itemExistence[$collectionName] = $this->loadItemExistence($collectionName, $idPropertyName);
        }

        if (!isset($this->itemExistence[$collectionName][substr($path, $delimiterPos + 1)])) {
            throw $this->createRefNotFoundException($ref);
        }

        return $ref;
    }

    private function getRefPath(string $ref): string
    {
        if (null === $this->componentsPrefixLength) {
            $this->componentsPrefixLength = \strlen(OA\Components::COMPONENTS_PREFIX);
        }

        return substr($ref, $this->componentsPrefixLength);
    }

    private function getCollectionIdentifierPropertyName(string $collectionName): ?string
    {
        if (null === $this->collectionIdentifiers) {
            $this->collectionIdentifiers = [];
            foreach (OA\Components::$_nested as $nested) {
                if (\is_array($nested) && \count($nested) === 2) {
                    $this->collectionIdentifiers[$nested[0]] = $nested[1];
                }
            }
        }

        return $this->collectionIdentifiers[$collectionName] ?? null;
    }

    private function loadItemExistence(string $collectionName, string $idPropertyName): array
    {
        $itemExistence = [];
        if ($this->components instanceof OA\Components) {
            $collection = $this->components->{$collectionName};
            if (!Generator::isDefault($collection)) {
                foreach ($collection as $item) {
                    $itemExistence[$item->{$idPropertyName}] = true;
                }
            }
        }

        return $itemExistence;
    }

    private function createRefNotFoundException(string $ref): \InvalidArgumentException
    {
        return new \InvalidArgumentException(sprintf('$ref "%s" not found', $ref));
    }
}
