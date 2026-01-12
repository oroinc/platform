<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after template fixtures have been loaded.
 *
 * This event allows listeners to inspect and modify the loaded template fixture entities
 * before they are used for export template generation or import validation. Listeners
 * can access the entities and modify them as needed.
 */
class LoadTemplateFixturesEvent extends Event
{
    /** @var array */
    protected $entities;

    public function __construct(array $entities)
    {
        $this->entities = $entities;
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        return $this->entities;
    }
}
