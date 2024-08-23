<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Tells if SVG icons is supported by the specified storefront theme.
 * If the setting is not set explicitly then it is inherited from the parent themes.
 */
class SvgIconsSupportProvider implements ResetInterface
{
    private const string CACHE_KEY = 'oro_layout.provider.svg_icons_support';

    public function __construct(
        private ThemeManager $themeManager,
        private CacheInterface&CacheItemPoolInterface $cache
    ) {
    }

    public function isSvgIconsSupported(string $themeName): bool
    {
        return $this->cache->get(self::CACHE_KEY . '.theme.' . $themeName, function () use ($themeName) {
            if (!$this->themeManager->hasTheme($themeName)) {
                return false;
            }

            $themes = $this->themeManager->getThemesHierarchy($themeName);
            foreach (array_reverse($themes) as $theme) {
                $isSupported = $theme->isSvgIconsSupport();
                if ($isSupported !== null) {
                    return $isSupported;
                }
            }

            return false;
        });
    }

    public function reset(): void
    {
        $this->cache->clear(self::CACHE_KEY);
    }
}
