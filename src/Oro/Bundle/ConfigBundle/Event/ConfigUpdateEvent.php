<?php

namespace Oro\Bundle\ConfigBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class ConfigUpdateEvent extends Event
{
    const EVENT_NAME = 'oro_config.update_after';

    /** @var array */
    protected $changeSet = [];

    /**
     * @param ConfigManager $cm
     * @param array         $updateChangeSet
     * @param array         $deleteChangeSet
     */
    public function __construct(ConfigManager $cm, array $updateChangeSet, array $deleteChangeSet)
    {
        foreach ($deleteChangeSet as $key) {
            $key = implode(ConfigManager::SECTION_MODEL_SEPARATOR, $key);

            $this->changeSet[$key] = [
                'old' => $cm->get($key),
                'new' => $cm->get($key, true)
            ];
        }

        foreach ($updateChangeSet as $key => $value) {
            $key   = str_replace(ConfigManager::SECTION_VIEW_SEPARATOR, ConfigManager::SECTION_MODEL_SEPARATOR, $key);
            $value = is_array($value) ? $value['value'] : $value;

            $this->changeSet[$key] = [
                'old' => $cm->get($key),
                'new' => $value
            ];
        }

        // prevent case when old value is the same as new
        $this->changeSet = array_filter(
            $this->changeSet,
            function ($changeSet) {
                return $changeSet['old'] != $changeSet['new'];
            }
        );
    }

    /**
     * Returns config change set
     *
     * [
     *     ...
     *
     *     KEY => [
     *        'new' => VALUE,
     *        'old' => VALUE
     *     ],
     *
     *    ....
     * ]
     *
     * @return array
     */
    public function getChangeSet()
    {
        return $this->changeSet;
    }

    /**
     * Checks whenever configuration value changed for give key
     *
     * @param string $key
     *
     * @return bool
     */
    public function isChanged($key)
    {
        return !empty($this->changeSet[$key]);
    }

    /**
     * Retrieve new value from change set
     *
     * @param string $key
     *
     * @return mixed
     * @throws \LogicException
     *
     */
    public function getNewValue($key)
    {
        if (!$this->isChanged($key)) {
            throw new \LogicException('Could not retrieve value for given key');
        }

        return $this->changeSet[$key]['new'];
    }

    /**
     * Retrieve old value from change set
     *
     * @param string $key
     *
     * @return mixed
     * @throws \LogicException
     */
    public function getOldValue($key)
    {
        if (!$this->isChanged($key)) {
            throw new \LogicException('Could not retrieve value for given key');
        }

        return $this->changeSet[$key]['old'];
    }
}
