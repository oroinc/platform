<?php

namespace Oro\Bundle\LayoutBundle\Theme;

use Oro\Bundle\LayoutBundle\Model\Theme;

class ThemeManager
{
    /** @var ThemeFactoryInterface */
    protected $themeFactory;

    /** @var array */
    protected $themeDefinitions;

    /** @var string */
    protected $activeTheme;

    /** @var Theme[] */
    protected $instances = [];

    /**
     * @param ThemeFactoryInterface $themeFactory
     * @param array                 $themeDefinitions
     * @param string|null           $activeTheme
     */
    public function __construct(ThemeFactoryInterface $themeFactory, array $themeDefinitions, $activeTheme = null)
    {
        $this->themeDefinitions = $themeDefinitions;
        $this->activeTheme      = $activeTheme;
        $this->themeFactory     = $themeFactory;
    }

    /**
     * @param string $activeTheme Theme name
     */
    public function setActiveTheme($activeTheme)
    {
        $this->activeTheme = $activeTheme;
    }

    /**
     * @return string
     */
    public function getActiveTheme()
    {
        return $this->activeTheme;
    }

    /**
     * Returns all known themes names
     *
     * @return string[]
     */
    public function getThemeNames()
    {
        return array_keys($this->themeDefinitions);
    }

    /**
     * Check whether given theme is known by manager
     *
     * @param string $themeName
     *
     * @return bool
     */
    public function hasTheme($themeName)
    {
        return isset($this->themeDefinitions[$themeName]);
    }

    /**
     * Gets theme model instance
     *
     * @param string $themeName
     *
     * @return Theme
     */
    public function getTheme($themeName = null)
    {
        $themeName = null === $themeName ? $this->activeTheme : $themeName;

        if (null === $themeName) {
            throw new \LogicException('Impossible to retrieve active theme due to miss configuration');
        } elseif (!$this->hasTheme($themeName)) {
            throw new \LogicException(sprintf('Unable to retrieve definition for theme "%s"', $themeName));
        }

        if (!isset($this->instances[$themeName])) {
            $this->instances[$themeName] = $this->themeFactory->create($themeName, $this->themeDefinitions[$themeName]);
        }

        return $this->instances[$themeName];
    }

    /**
     * @param null|string|array $groups
     *
     * @return \Oro\Bundle\LayoutBundle\Model\Theme[]
     */
    public function getAllThemes($groups = null)
    {
        $names = $this->getThemeNames();

        $themes = array_combine(
            $names,
            array_map(
                function ($themeName) {
                    return $this->getTheme($themeName);
                },
                $names
            )
        );

        if (!empty($groups)) {
            $groups = is_array($groups) ? $groups : [$groups];
            $themes = array_filter(
                $themes,
                function (Theme $theme) use ($groups) {
                    return count(array_intersect($groups, $theme->getGroups())) > 0;
                }
            );
        }

        return $themes;
    }
}
