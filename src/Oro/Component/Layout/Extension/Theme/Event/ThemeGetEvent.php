<?php

declare(strict_types=1);

namespace Oro\Component\Layout\Extension\Theme\Event;

use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The abstract event class for event classes that are executed when a theme option value is retrieved.
 */
abstract class ThemeGetEvent extends Event
{
    public function __construct(
        private readonly ThemeManager $themeManager,
        private readonly string $themeName,
        private readonly string $optionName,
        private readonly bool $inherited = true,
        private mixed $value = null
    ) {
    }

    public function getThemeManager(): ThemeManager
    {
        return $this->themeManager;
    }

    public function getThemeName(): string
    {
        return $this->themeName;
    }

    public function getOptionName(): string
    {
        return $this->optionName;
    }

    public function isInherited(): bool
    {
        return $this->inherited;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }
}
