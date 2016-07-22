<?php

namespace Oro\Bundle\ConfigBundle\Api\Model;

class ConfigurationSection
{
    /** @var string */
    protected $id;

    /** @var ConfigurationOption[] */
    protected $options = [];

    /**
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return ConfigurationOption[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param ConfigurationOption[] $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }
}
