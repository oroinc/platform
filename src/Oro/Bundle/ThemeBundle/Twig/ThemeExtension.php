<?php

namespace Oro\Bundle\ThemeBundle\Twig;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to render back-office theme logo and icon:
 *   - oro_theme_logo
 *   - oro_theme_icon
 */
class ThemeExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_theme_logo', [$this, 'getThemeLogo']),
            new TwigFunction('oro_theme_icon', [$this, 'getThemeIcon']),
        ];
    }

    /**
     * @return string
     */
    public function getThemeLogo()
    {
        $result = '';
        $activeTheme = $this->getThemeRegistry()->getActiveTheme();
        if ($activeTheme) {
            $result = $activeTheme->getLogo();
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getThemeIcon()
    {
        $result = '';
        $activeTheme = $this->getThemeRegistry()->getActiveTheme();
        if ($activeTheme) {
            $result = $activeTheme->getIcon();
        }

        return $result;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ThemeRegistry::class
        ];
    }

    private function getThemeRegistry(): ThemeRegistry
    {
        return $this->container->get(ThemeRegistry::class);
    }
}
