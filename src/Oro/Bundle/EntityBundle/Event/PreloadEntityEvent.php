<?php

namespace Oro\Bundle\EntityBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents preloading event for entities.
 */
class PreloadEntityEvent extends Event
{
    public const EVENT_NAME = 'oro_entity.preload_entity';

    /**
     * @var array Array of base entities to preload fields in.
     */
    private $entities;

    /**
     * @var string[] A list of fields to preload only in base entities.
     */
    private $fieldsToPreload;

    /**
     * @var array A tree of fields and subfields to preload in $entities, for example:
     *  [
     *      'product' => [
     *          'names' => [],
     *          'images' => ['image' => []],
     *      ],
     *  ]
     */
    private $allFieldsToPreload;

    /**
     * @var array Arbitrary data
     */
    private $context;

    /**
     * @param array $entities Entities in which it is needed to preload fields.
     * @param array $fieldsToPreload A tree of fields and subfields to preload in $entities.
     * @param array $context Preloading context, can contain any data.
     */
    public function __construct(array $entities, array $fieldsToPreload, array $context)
    {
        $this->entities = $entities;
        $this->fieldsToPreload = array_keys($fieldsToPreload);
        $this->allFieldsToPreload = $fieldsToPreload;
        $this->context = $context;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * @return string[]
     */
    public function getFieldsToPreload(): array
    {
        return $this->fieldsToPreload;
    }

    public function hasSubFields(string $field): bool
    {
        return !empty($this->allFieldsToPreload[$field]);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
