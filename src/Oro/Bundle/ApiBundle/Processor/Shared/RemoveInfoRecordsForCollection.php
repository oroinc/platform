<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * Removes records with key "_" from primary collection and to-many associations of each result item.
 * Such records contain an additional information about a collection, e.g. "has_more" flag
 * in such record indicates whether a collection has more records than it was requested.
 * All removed records are stored in the context for further usage.
 */
class RemoveInfoRecordsForCollection extends RemoveInfoRecords
{
    /**
     * {@inheritdoc}
     */
    protected function processData(array &$data, EntityMetadata $metadata): array
    {
        return $this->processEntities($data, $metadata, '');
    }
}
