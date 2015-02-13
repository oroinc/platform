<?php

namespace Oro\Bundle\LayoutBundle\Model;

class Theme
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $parentTheme;

    /** @var string */
    protected $label;

    /** @var string */
    protected $logo;

    /** @var string */
    protected $screenshot;

    /** @var string */
    protected $directory;

    /** @var bool */
    protected $hidden = false;

    /**
     * @param string $name
     * @param        $parentTheme
     */
    public function __construct($name, $parentTheme = null)
    {
        $this->name        = $name;
        $this->parentTheme = $parentTheme;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getParentTheme()
    {
        return $this->parentTheme;
    }

    /**
     * @param string $parentTheme
     */
    public function setParentTheme($parentTheme)
    {
        $this->parentTheme = $parentTheme;
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param string $logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    /**
     * @return string
     */
    public function getScreenshot()
    {
        return $this->screenshot;
    }

    /**
     * @param string $screenshot
     */
    public function setScreenshot($screenshot)
    {
        $this->screenshot = $screenshot;
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return null === $this->directory ? $this->name : $this->directory;
    }

    /**
     * @param string $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return boolean
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * @param boolean $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }
}
