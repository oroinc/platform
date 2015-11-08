<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ExtendSchemaUpdateEvent extends Event
{
    const NAME = 'extend_schema.successful_update';

    /** @var bool */
    protected $updateRouting;

    /**
     * @param bool $updateRouting
     */
    public function __construct($updateRouting)
    {
        $this->updateRouting = $updateRouting;
    }

    /**
     * @return bool
     */
    public function isUpdateRouting()
    {
        return $this->updateRouting;
    }
}
