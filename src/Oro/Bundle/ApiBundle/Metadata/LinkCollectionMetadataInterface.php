<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * An interface for the metadata that represents a collection of links related to a particular entity.
 */
interface LinkCollectionMetadataInterface extends LinkMetadataInterface
{
    /**
     * Gets a list of links applicable to the given result data,
     *
     * @param DataAccessorInterface $dataAccessor
     *
     * @return LinkMetadataInterface[] [link name => LinkMetadataInterface, ...]
     */
    public function getLinks(DataAccessorInterface $dataAccessor): array;
}
