<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

class NewEntitiesHelper
{
    /**
     * Entities which can be reused to prevent duplicating,
     * accessible by unique key
     *
     * @var array
     */
    protected $newEntities = [];

    /**
     * Could be used for count usage of objects in some context
     *
     * @var array ['context_key + unique_object_key' => $usageCount]
     */
    protected $newEntitiesUsages = [];

    /**
     * @param string $key
     * @param null   $default
     *
     * @return object|null
     */
    public function getEntity($key, $default = null)
    {
        return isset($this->newEntities[$key])
            ? $this->newEntities[$key]
            : $default;
    }

    /**
     * @param string $key
     * @param object $value
     */
    public function setEntity($key, $value)
    {
        $this->newEntities[$key] = $value;
    }

    /**
     * @param string $hashKey
     */
    public function incrementEntityUsage($hashKey)
    {
        if (!isset($this->newEntitiesUsages[$hashKey])) {
            $this->newEntitiesUsages[$hashKey] = 0;
        }
        $this->newEntitiesUsages[$hashKey]++;
    }

    /**
     * @param string $hashKey
     *
     * @return int
     */
    public function getEntityUsage($hashKey)
    {
        return isset($this->newEntitiesUsages[$hashKey])
            ? $this->newEntitiesUsages[$hashKey]
            : 0;
    }

    public function onFlush()
    {
        $this->newEntities       = [];
        $this->newEntitiesUsages = [];
    }

    public function onClear()
    {
        $this->newEntities       = [];
        $this->newEntitiesUsages = [];
    }
}
