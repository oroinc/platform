<?php

declare(strict_types=1);

namespace Oro\Component\Layout\Extension\Theme\Event;

/**
 * The event that is fired when a theme option value is retrieved.
 * It allows to make an additional transformation of the theme option value.
 */
class ThemeOptionGetEvent extends ThemeGetEvent
{
    public const NAME = 'oro_theme.get_option';
}
