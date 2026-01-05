<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

interface EventTriggerInterface
{
    public const EVENT_CREATE = 'create';
    public const EVENT_UPDATE = 'update';
    public const EVENT_DELETE = 'delete';

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
