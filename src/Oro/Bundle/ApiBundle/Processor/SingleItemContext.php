<?php

namespace Oro\Bundle\ApiBundle\Processor;

class SingleItemContext extends Context
{
    /** an identifier of an entity */
    const ID = 'id';

    /** a configuration of an entity */
    const CONFIG = 'config';

    /**
     * Gets an identifier of an entity
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->get(self::ID);
    }

    /**
     * Sets an identifier of an entity
     *
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->set(self::ID, $id);
    }

    /**
     * Gets a configuration of an entity
     *
     * @return array|null
     */
    public function getConfig()
    {
        return $this->get(self::CONFIG);
    }

    /**
     * Sets a configuration of an entity
     *
     * @param array|null $config
     */
    public function setConfig($config)
    {
        $this->set(self::CONFIG, $config);
    }
}
