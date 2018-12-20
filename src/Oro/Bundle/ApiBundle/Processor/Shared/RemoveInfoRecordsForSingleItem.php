<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * Removes records with key "_" from to-many associations of a result item.
 * Such records contain an additional information about a collection, e.g. "has_more" flag
 * in such record indicates whether a collection has more records than it was requested.
 * All removed records are stored in the context for further usage.
 */
class RemoveInfoRecordsForSingleItem extends RemoveInfoRecords
{
    /**
     * {@inheritdoc}
     */
    protected function processData(array &$data, EntityMetadata $metadata): array
    {
        return $this->processEntity($data, $metadata, '');
    }
}
