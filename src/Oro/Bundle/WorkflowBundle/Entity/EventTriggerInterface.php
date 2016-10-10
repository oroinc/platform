<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

interface EventTriggerInterface
{
    const EVENT_CREATE = 'create';
    const EVENT_UPDATE = 'update';
    const EVENT_DELETE = 'delete';

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getEntityClass();

    /**
     * @return string
     */
    public function getEvent();

    /**
     * @return string
     */
    public function getField();
}
