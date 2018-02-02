<?php

namespace Oro\Bundle\SearchBundle\Transformer;

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
