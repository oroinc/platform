<?php

namespace Oro\Bundle\SoapBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after a single entity is found via the SOAP API.
 *
 * This event allows listeners to perform post-processing on the retrieved entity,
 * such as applying additional transformations, validations, or side effects after
 * the entity has been fetched from the database.
 */
class FindAfter extends Event
{
    public const NAME = 'oro_api.request.find.after';

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
