<?php

namespace Oro\Bundle\ThemeBundle\EventListener;

use Oro\Bundle\AsseticBundle\Event\LoadCssEvent;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

class LoadCssEventListener
{
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
     * @param LoadCssEvent $event
     */
    public function onLoadCss(LoadCssEvent $event)
    {
        $activeTheme = $this->themeRegistry->getActiveTheme();
        if ($activeTheme) {
            $event->addCss('theme', $activeTheme->getStyles());
        }
    }
}
