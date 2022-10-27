<?php

namespace Oro\Bundle\ApiBundle\Batch;

/**
 * Provides a way to build an unique key of an included entity or a relationship.
 */
class ItemKeyBuilder
{
    /**
     * Builds an unique key of an included entity or a relationship.
     */
    public function buildItemKey(string $type, string $id): string
    {
        return $type . '|' . $id;
    }
}
