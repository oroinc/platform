<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

class ThemeManager
{
    /** @var ThemeFactoryInterface */
    protected $themeFactory;

    /** @var array */
    protected $themeDefinitions;

    /** @var Theme[] */
    protected $instances = [];

    /**
     * @param ThemeFactoryInterface $themeFactory
     * @param array                 $themeDefinitions
     */
    public function __construct(ThemeFactoryInterface $themeFactory, array $themeDefinitions)
    {
        $this->themeFactory     = $themeFactory;
        $this->themeDefinitions = $themeDefinitions;
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
    public function getTheme($themeName)
    {
        if (empty($themeName)) {
            throw new \InvalidArgumentException('The theme name must not be empty.');
        } elseif (!$this->hasTheme($themeName)) {
            throw new \LogicException(sprintf('Unable to retrieve definition for theme "%s".', $themeName));
        }

        if (!isset($this->instances[$themeName])) {
            $this->instances[$themeName] = $this->themeFactory->create($themeName, $this->themeDefinitions[$themeName]);
        }

        return $this->instances[$themeName];
    }

    /**
     * @param null|string|array $groups
     *
     * @return Theme[]
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
