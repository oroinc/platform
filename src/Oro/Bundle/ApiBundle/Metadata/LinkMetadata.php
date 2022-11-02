<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Exception\LinkHrefResolvingFailedException;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The metadata that represents a link related to a particular entity.
 */
abstract class LinkMetadata implements LinkMetadataInterface
{
    /** @var MetaAttributeMetadata[] */
    private array $metaProperties = [];

    /**
     * Gets the link's URL based on the given result data.
     *
     * @param DataAccessorInterface $dataAccessor
     *
     * @return string|null The link's URL or NULL if the link is not applicable the given result data
     *
     * @throws LinkHrefResolvingFailedException when it is not possible to resolve the link's URL
     *                                          because of not enough data to build the URL
     */
    abstract public function getHref(DataAccessorInterface $dataAccessor): ?string;

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->metaProperties = ConfigUtil::cloneObjects($this->metaProperties);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $result = [];
        $metaProperties = ConfigUtil::convertPropertiesToArray($this->metaProperties);
        if (!empty($metaProperties)) {
            $result['meta_properties'] = $metaProperties;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetaProperties(): array
    {
        return $this->metaProperties;
    }

    /**
     * {@inheritDoc}
     */
    public function hasMetaProperty(string $metaPropertyName): bool
    {
        return isset($this->metaProperties[$metaPropertyName]);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetaProperty(string $metaPropertyName): ?MetaAttributeMetadata
    {
        return $this->metaProperties[$metaPropertyName] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function addMetaProperty(MetaAttributeMetadata $metaProperty): MetaAttributeMetadata
    {
        $this->metaProperties[$metaProperty->getName()] = $metaProperty;

        return $metaProperty;
    }

    /**
     * {@inheritDoc}
     */
    public function removeMetaProperty(string $metaPropertyName): void
    {
        unset($this->metaProperties[$metaPropertyName]);
    }

    /**
     * @param DataAccessorInterface $dataAccessor
     * @param array                 $params
     *
     * @return array [name => value, ..]
     */
    protected function resolveParameters(DataAccessorInterface $dataAccessor, array $params): array
    {
        $result = [];
        foreach ($params as $name => $propertyPath) {
            $value = null;
            if ($dataAccessor->tryGetValue($propertyPath ?: $name, $value)) {
                $result[$name] = $value;
            }
        }

        return $result;
    }
}
