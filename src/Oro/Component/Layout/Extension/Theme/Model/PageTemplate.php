<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

class PageTemplate
{
    /** @var string */
    private $label;

    /** @var string */
    private $description;

    /** @var string */
    private $screenshot;

    /** @var string */
    private $key;

    /** @var string */
    private $routeName;

    /** @var bool */
    private $enabled;

    /**
     * @param string $key
     * @param string $label
     * @param string $routeName
     */
    public function __construct($label, $key, $routeName)
    {
        $this->label = $label;
        $this->key = $key;
        $this->routeName = $routeName;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $screenshot
     * @return $this
     */
    public function setScreenshot($screenshot)
    {
        $this->screenshot = $screenshot;

        return $this;
    }

    /**
     * @return string
     */
    public function getScreenshot()
    {
        return $this->screenshot;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $routeName
     * @return $this
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;

        return $this;
    }

    /**
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @param PageTemplate $pageTemplate
     * @return $this
     */
    public function mergeParent(PageTemplate $pageTemplate)
    {
        if ($this->getDescription() === null) {
            $this->setDescription($pageTemplate->getDescription());
        }

        if ($this->getScreenshot() === null) {
            $this->setScreenshot($pageTemplate->getScreenshot());
        }

        if ($this->isEnabled() === null) {
            $this->setEnabled($pageTemplate->isEnabled());
        }

        return $this;
    }
}
