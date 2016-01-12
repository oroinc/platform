<?php

namespace Oro\Bundle\ActivityBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event allow to change aliases config before the usage in context search
 */
class SearchAliasesEvent extends Event
{
    const EVENT_NAME = 'oro_activity.search_aliases';

    /** @var array */
    protected $aliases;

    /** @var array */
    protected $targetClasses;

    /**
     * @param array $aliases
     * @param array $targetClasses
     */
    public function __construct($aliases, $targetClasses)
    {
        $this->aliases = $aliases;
        $this->targetClasses = $targetClasses;
    }

    /**
     * Return aliases config array
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Set the aliases config array
     *
     * @param array $aliases
     */
    public function setAliases($aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * Return target classes array
     *
     * @return array
     */
    public function getTargetClasses()
    {
        return $this->targetClasses;
    }
}
