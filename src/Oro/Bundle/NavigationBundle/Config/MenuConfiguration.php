<?php

namespace Oro\Bundle\NavigationBundle\Config;

class MenuConfiguration
{
    /** @var array */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getTree()
    {
        if (!array_key_exists('tree', $this->config)) {
            return [];
        }

        return $this->config['tree'];
    }

    /**
     * @return array
     */
    public function getItems()
    {
        if (!array_key_exists('items', $this->config)) {
            return [];
        }

        return $this->config['items'];
    }

    /**
     * @return array
     */
    public function getTemplates()
    {
        if (!array_key_exists('templates', $this->config)) {
            return [];
        }


        return $this->config['templates'];
    }
}
