<?php

namespace Oro\Bundle\SearchBundle\Transformer;

/**
 * Defines the contract for transforming entities into reindexing messages.
 *
 * This interface specifies the method for converting entity objects into message
 * arrays suitable for asynchronous processing. Implementations handle batching
 * and formatting of entity data for message queue systems used in search indexing.
 */
interface MessageTransformerInterface
{
    const MESSAGE_FIELD_ENTITY_CLASS = 'class';
    const MESSAGE_FIELD_ENTITY_IDS = 'entityIds';

    /**
     * Transform data to queue messages
     *
     * @param array $entities
     * @param array $context
     *
     * @return array
     */
    public function transform(array $entities, array $context = []);
}
