<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Exception\LinkHrefResolvingFailedException;
use Oro\Component\ChainProcessor\ToArrayInterface;

/**
 * An interface for the metadata that represents a link related to a particular entity.
 */
interface LinkMetadataInterface extends ToArrayInterface
{
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
    public function getHref(DataAccessorInterface $dataAccessor): ?string;

    /**
     * Gets metadata for all meta properties.
     *
     * @return MetaAttributeMetadata[] [meta property name => MetaAttributeMetadata, ...]
     */
    public function getMetaProperties(): array;

    /**
     * Checks whether metadata of the given meta property exists.
     *
     * @param string $metaPropertyName
     *
     * @return bool
     */
    public function hasMetaProperty(string $metaPropertyName): bool;

    /**
     * Gets metadata of a meta property.
     *
     * @param string $metaPropertyName
     *
     * @return MetaAttributeMetadata|null
     */
    public function getMetaProperty(string $metaPropertyName): ?MetaAttributeMetadata;

    /**
     * Adds metadata of a meta property.
     *
     * @param MetaAttributeMetadata $metaProperty
     *
     * @return MetaAttributeMetadata
     */
    public function addMetaProperty(MetaAttributeMetadata $metaProperty): MetaAttributeMetadata;

    /**
     * Removes metadata of a meta property.
     *
     * @param string $metaPropertyName
     */
    public function removeMetaProperty(string $metaPropertyName): void;
}
