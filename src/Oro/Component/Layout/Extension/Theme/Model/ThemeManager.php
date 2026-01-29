<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

use Oro\Component\Layout\Extension\Theme\Event\ThemeConfigOptionGetEvent;
use Oro\Component\Layout\Extension\Theme\Event\ThemeOptionGetEvent;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The main entry point for layout themes.
 */
class ThemeManager implements ResetInterface
{
    private const string CACHE_THEME_OPTION_KEY = 'oro_theme.option';
    private const string CACHE_THEME_CONFIG_OPTION_KEY = 'oro_theme.config_option';
    private const string INHERITED_THEME_CONFIG_NAMESPACE = 'config.';

    /** @var ThemeFactoryInterface */
    private $themeFactory;

    /** @var ThemeDefinitionBagInterface */
    private $themeDefinitionBag;

    /** @var Theme[] */
    private $instances = [];

    /** @var array local cache with all the themes  */
    private array $themes = [];

    private EventDispatcherInterface $dispatcher;
    private CacheInterface&CacheItemPoolInterface $cache;
    private PropertyAccessorInterface $propertyAccessor;
    private array $inheritedThemeOptions = [];

    /**
     * @var string[]
     */
    private array $enabledThemes;

    /**
     * @param ThemeFactoryInterface       $themeFactory
     * @param ThemeDefinitionBagInterface $themeDefinitionBag
     * @param string[] $enabledThemes
     */
    public function __construct(
        ThemeFactoryInterface $themeFactory,
        ThemeDefinitionBagInterface $themeDefinitionBag,
        array $enabledThemes,
    ) {
        $this->themeFactory = $themeFactory;
        $this->themeDefinitionBag = $themeDefinitionBag;
        $this->enabledThemes = $enabledThemes;
    }

    public function setEventDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function setThemeManagerCache(CacheInterface&CacheItemPoolInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor): void
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function setInheritedThemeOptions(array $inheritedThemeOptions): void
    {
        $this->inheritedThemeOptions = $inheritedThemeOptions;
        $this->normalizeInheritedThemeOptions();
    }

    /**
     * Returns all known themes names
     *
     * @return string[]
     */
    public function getThemeNames()
    {
        return $this->themeDefinitionBag->getThemeNames();
    }

    public function getEnabledThemes($groups = null): array
    {
        $themes = $this->getAllThemes($groups);

        if ($this->enabledThemes !== []) {
            $enabledThemes = array_intersect_key($themes, array_flip($this->enabledThemes));

            if ($enabledThemes !== []) {
                return $enabledThemes;
            }
        }

        return $themes;
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
        return null !== $this->themeDefinitionBag->getThemeDefinition($themeName);
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
        }
        if (!$this->hasTheme($themeName)) {
            throw new \LogicException(sprintf('Unable to retrieve definition for theme "%s".', $themeName));
        }

        if (!isset($this->instances[$themeName])) {
            $theme = $this->themeFactory->create(
                $themeName,
                $this->themeDefinitionBag->getThemeDefinition($themeName)
            );
            $this->instances[$themeName] = $this->mergePageTemplates($theme);
        }

        return $this->instances[$themeName];
    }

    /**
     * @param Theme $theme
     *
     * @return Theme
     */
    private function mergePageTemplates(Theme $theme)
    {
        if ($theme->getParentTheme()) {
            $parentTheme = $this->getTheme($theme->getParentTheme());

            foreach ($parentTheme->getPageTemplates() as $parentPageTemplate) {
                $theme->addPageTemplate($parentPageTemplate);
            }

            foreach ($parentTheme->getPageTemplateTitles() as $route => $title) {
                if (!$theme->getPageTemplateTitle($route)) {
                    $theme->addPageTemplateTitle($route, $title);
                }
            }
        }

        return $theme;
    }

    /**
     * @param null|string|array $groups
     *
     * @return Theme[]
     */
    public function getAllThemes($groups = null)
    {
        $cacheKey = $groups === null ? 'all' : implode(',', (array)$groups);
        if (array_key_exists($cacheKey, $this->themes) && $this->themes[$cacheKey] !== null) {
            return $this->themes[$cacheKey];
        }

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

        $this->themes[$cacheKey] = $themes;

        return $themes;
    }

    /**
     * Returns the theme hierarchy for the specified $themeName. Root theme is as first item.
     *
     * @param string $themeName
     *
     * @return Theme[]
     */
    public function getThemesHierarchy(string $themeName): array
    {
        $themesHierarchy = [];

        do {
            $theme = $this->getTheme($themeName);

            $themesHierarchy[] = $theme;
        } while ($themeName = $theme->getParentTheme());

        return array_reverse($themesHierarchy);
    }

    public function themeHasParent(string $theme, array $parentThemes): bool
    {
        $hierarchy = $this->getThemesHierarchy($theme);
        foreach ($hierarchy as $currentTheme) {
            if (\in_array($currentTheme->getName(), $parentThemes)) {
                return true;
            }
        }
        return false;
    }

    public function getThemeConfigOption(string $themeName, string $configOptionName, bool $inherited = true): mixed
    {
        $cacheKey = self::CACHE_THEME_CONFIG_OPTION_KEY . $themeName . $configOptionName . $inherited;
        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        if (\in_array($configOptionName, $this->inheritedThemeOptions['config'] ?? [], true) && $inherited) {
            $value = $this->getHierarchyThemeOption($themeName, $configOptionName, true);
        } else {
            $value = $this->getTheme($themeName)->getConfigByKey($configOptionName);
        }

        $event = new ThemeConfigOptionGetEvent($this, $themeName, $configOptionName, $inherited, $value);
        $this->dispatcher->dispatch($event, ThemeConfigOptionGetEvent::NAME);
        $this->dispatcher->dispatch($event, \sprintf('%s.%s', ThemeConfigOptionGetEvent::NAME, $configOptionName));

        $this->cache->save($cacheItem->set($event->getValue()));
        return $cacheItem->get();
    }

    public function getThemeOption(string $themeName, string $optionName, bool $inherited = true): mixed
    {
        $cacheKey = self::CACHE_THEME_OPTION_KEY . $themeName . $optionName . $inherited;
        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        if (\in_array($optionName, $this->inheritedThemeOptions, true) && $inherited) {
            $value = $this->getHierarchyThemeOption($themeName, $optionName);
        } else {
            $value = $this->propertyAccessor->getValue($this->getTheme($themeName), $optionName);
        }

        $event = new ThemeOptionGetEvent($this, $themeName, $optionName, $inherited, $value);
        $this->dispatcher->dispatch($event, ThemeOptionGetEvent::NAME);
        $this->dispatcher->dispatch($event, \sprintf('%s.%s', ThemeOptionGetEvent::NAME, $optionName));

        $this->cache->save($cacheItem->set($event->getValue()));
        return $cacheItem->get();
    }

    #[\Override]
    public function reset(): void
    {
        $this->instances = [];
        $this->themes = [];
    }

    private function getHierarchyThemeOption(string $themeName, string $optionName, bool $isConfig = false): mixed
    {
        $themes = $this->getThemesHierarchy($themeName);

        $values = [];
        foreach ($themes as $theme) {
            $optionValue = $isConfig ?
                $theme->getConfigByKey($optionName) :
                $this->propertyAccessor->getValue($theme, $optionName);

            if ($optionValue !== null && $optionValue !== []) {
                $values[] = [$optionName => $optionValue];
            }
        }

        $value = \array_merge(...$values);

        return $value[$optionName] ?? null;
    }

    private function normalizeInheritedThemeOptions(): void
    {
        foreach ($this->inheritedThemeOptions as $key => $option) {
            if (!\str_contains($option, self::INHERITED_THEME_CONFIG_NAMESPACE)) {
                continue;
            }

            [$configKey, $configName] = \explode('.', $option);
            $this->inheritedThemeOptions[$configKey][] = $configName;
            unset($this->inheritedThemeOptions[$key]);
        }
    }
}
