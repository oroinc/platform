<?php

namespace Oro\Bundle\ThemeBundle\Twig;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ThemeExtension extends \Twig_Extension
{
    const NAME = 'oro_theme';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ThemeRegistry
     */
    protected function getThemeRegistry()
    {
        return $this->container->get('oro_theme.registry');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_theme_logo', [$this, 'getThemeLogo']),
            new \Twig_SimpleFunction('oro_theme_icon', [$this, 'getThemeIcon']),
        ];
    }

    /**
     * Get theme logo
     *
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
     * Get theme icon
     *
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
