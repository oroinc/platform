<?php

namespace Oro\Bundle\ConfigBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ConfigUpdateEvent extends Event
{
    const EVENT_NAME = 'oro_config.update_after';

    /** @var array */
    protected $changeSet = [];

    /**
     * @param array $changeSet
     */
    public function __construct(array $changeSet)
    {
        $this->changeSet = $changeSet;
    }

    /**
     * Returns config change set
     *
     * @return array [name => ['new' => value, 'old' => value], ...]
     */
    public function getChangeSet()
    {
        return $this->changeSet;
    }

    /**
     * Checks whenever configuration value is changed
     *
     * @param string $name
     *
     * @return bool
     */
    public function isChanged($name)
    {
        return !empty($this->changeSet[$name]);
    }

    /**
     * Retrieve new value from change set
     *
     * @param string $name
     *
     * @return mixed
     * @throws \LogicException
     *
     */
    public function getNewValue($name)
    {
        if (!$this->isChanged($name)) {
            throw new \LogicException('Could not retrieve value for given key');
        }

        return $this->changeSet[$name]['new'];
    }

    /**
     * Retrieve old value from change set
     *
     * @param string $name
     *
     * @return mixed
     * @throws \LogicException
     */
    public function getOldValue($name)
    {
        if (!$this->isChanged($name)) {
            throw new \LogicException('Could not retrieve value for given key');
        }

        return $this->changeSet[$name]['old'];
    }
}
