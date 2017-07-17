<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Suite;

use Behat\Testwork\Suite\Suite;

class SuiteConfiguration implements Suite
{
    protected $name;

    protected $settings;

    protected $type;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings()
    {
        return $this->settings;
    }

    public function setSetting($key, $value)
    {
        $this->settings[$key] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSetting($key)
    {
        return array_key_exists($key, $this->getSettings());
    }

    /**
     * {@inheritdoc}
     */
    public function getSetting($key)
    {
        if (!$this->hasSetting($key)) {
            throw new \InvalidArgumentException(sprintf('There is no "%s" key in settings', $key));
        }

        return $this->settings[$key];
    }

    public function getPaths()
    {
        return $this->getSetting('paths');
    }
}
