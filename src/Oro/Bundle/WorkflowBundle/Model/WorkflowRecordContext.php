<?php

namespace Oro\Bundle\WorkflowBundle\Model;

class WorkflowRecordContext
{
    /**
     * @var object
     */
    private $entity;

    /**
     * @param object $entity
     */
    public function __construct($entity)
    {
        if (!is_object($entity)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Instance of entity object is required. Got `%s` instead.',
                    gettype($entity)
                )
            );
        }

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
