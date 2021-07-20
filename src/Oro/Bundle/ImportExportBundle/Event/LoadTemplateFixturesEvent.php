<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

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
