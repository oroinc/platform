<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * This interface should be implemented by filters that depend on an entity metadata.
 */
interface MetadataAwareFilterInterface
{
    /**
     * Sets the entity metadata.
     */
    public function setMetadata(EntityMetadata $metadata): void;
}
