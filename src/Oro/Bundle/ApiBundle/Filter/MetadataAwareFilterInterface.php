<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * This interface should be implemented by filters that depends on an entity metadata.
 */
interface MetadataAwareFilterInterface
{
    /**
     * Sets the entity metadata.
     *
     * @param EntityMetadata $metadata
     */
    public function setMetadata(EntityMetadata $metadata);
}
