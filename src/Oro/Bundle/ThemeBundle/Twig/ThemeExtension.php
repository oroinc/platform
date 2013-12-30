<?php

namespace Oro\Bundle\ThemeBundle\Twig;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

class ThemeExtension extends \Twig_Extension
{
    const NAME = 'oro_theme';

    /**
     * @var ThemeRegistry
     */
    protected $themeRegistry;

    /**
     * @param ThemeRegistry $themeRegistry
     */
    public function __construct(ThemeRegistry $themeRegistry)
    {
        $this->themeRegistry = $themeRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_theme_logo', array($this, 'getThemeLogo')),
            new \Twig_SimpleFunction('oro_theme_icon', array($this, 'getThemeIcon')),
        );
    }

    /**
     * Get theme logo
     *
     * @return string
     */
    public function getThemeLogo()
    {
        $result = '';
        $activeTheme = $this->themeRegistry->getActiveTheme();
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
        $activeTheme = $this->themeRegistry->getActiveTheme();
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
