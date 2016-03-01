<?php

namespace Oro\Bundle\ActivityBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event allow to change context title
 */
class PrepareContextTitleEvent extends Event
{
    const EVENT_NAME = 'oro_activity.context_title';

    /** @var array */
    protected $item;

    /** @var string */
    protected $targetClass;

    /**
     * @param array $item
     * @param string $targetClass
     */
    public function __construct($item, $targetClass)
    {
        $this->item = $item;
        $this->targetClass = $targetClass;
    }

    /**
     * Return item array
     *
     * @return array
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set the item array
     *
     * @param array $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    /**
     * Return target class
     *
     * @return string
     */
    public function getTargetClass()
    {
        return $this->targetClass;
    }
}
