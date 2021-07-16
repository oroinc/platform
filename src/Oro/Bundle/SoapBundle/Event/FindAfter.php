<?php

namespace Oro\Bundle\SoapBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class FindAfter extends Event
{
    const NAME = 'oro_api.request.find.after';

    /**
     * @var object
     */
    protected $entity;

    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
