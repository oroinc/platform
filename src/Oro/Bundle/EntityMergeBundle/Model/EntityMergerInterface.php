<?php

namespace Oro\Bundle\EntityMergeBundle\Model;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;

/**
 * Represents a service to merge entities.
 */
interface EntityMergerInterface
{
    /**
     * Merges entities.
     */
    public function merge(EntityData $data): void;
}
