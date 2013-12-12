<?php

namespace Oro\Bundle\ThemeBundle\Model;

use Oro\Bundle\ThemeBundle\Exception\ThemeNotFoundException;

class ThemeRegistry
{
    /**
     * @var array
     */
    protected $themeSettings;

    /**
     * @var string
     */
    protected $activeTheme;

    /**
     * @var Theme[]
     */
    protected $themes;

    /**
     * @param array $themeSettings
     */
    public function __construct(array $themeSettings)
    {
        $this->themeSettings = $themeSettings;
    }

    /**
     * @param string $activeTheme
     */
    public function setActiveTheme($activeTheme)
    {
        $this->activeTheme = $activeTheme;
    }

    /**
     * Gets instance of theme by name
     *
     * @param string $name
     * @return Theme
     * @throws ThemeNotFoundException
     */
    public function getTheme($name)
    {
        if (isset($this->themes[$name])) {
            return $this->themes[$name];
        }

        if (!isset($this->themeSettings[$name])) {
            throw new ThemeNotFoundException(sprintf('Theme "%s" not found.', $name));
        }

        $this->themes[$name] = $this->createTheme($name, $this->themeSettings[$name]);
        return $this->themes[$name];
    }

    /**
     * Gets list of all themes
     *
     * @return Theme[]
     */
    public function getAllThemes()
    {
        $result = array();
        foreach (array_keys($this->themeSettings) as $name) {
            $result[$name] = $this->getTheme($name);
        }
        return $result;
    }

    /**
     * Create instance of theme based on name and settings
     *
     * @param string $name
     * @param array $settings
     * @return Theme
     */
    protected function createTheme($name, array $settings)
    {
        $result = new Theme($name);
        if (isset($settings['styles'])) {
            $result->setStyles((array) $settings['styles']);
        }
        if (isset($settings['label'])) {
            $result->setLabel($settings['label']);
        }
        if (isset($settings['icon'])) {
            $result->setIcon($settings['icon']);
        }
        if (isset($settings['logo'])) {
            $result->setLogo($settings['logo']);
        }
        if (isset($settings['screenshot'])) {
            $result->setScreenshot($settings['screenshot']);
        }
        return $result;
    }

    /**
     * Gets instance of active theme if it's available, otherwise return null
     *
     * @return Theme|null
     */
    public function getActiveTheme()
    {
        if (!$this->activeTheme) {
            return null;
        }
        return $this->getTheme($this->activeTheme);
    }
}
