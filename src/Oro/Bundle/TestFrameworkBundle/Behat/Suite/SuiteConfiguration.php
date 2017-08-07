<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Suite;

use Behat\Testwork\Suite\Suite;

class SuiteConfiguration implements Suite
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var string|null
     */
    protected $type = null;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $settings
     * @return $this
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setSetting($key, $value)
    {
        $this->settings[$key] = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSetting($key)
    {
        return array_key_exists($key, $this->getSettings());
    }

    /**
     * @return mixed
     * @throws \InvalidArgumentException in case when setting is not exist
     */
    public function getSetting($key)
    {
        if (!$this->hasSetting($key)) {
            throw new \InvalidArgumentException(sprintf('There is no "%s" key in settings', $key));
        }

        return $this->settings[$key];
    }

    /**
     * @return array
     */
    public function getPaths()
    {
        return $this->getSetting('paths');
    }
}
