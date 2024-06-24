<?php

namespace Oro\Bundle\PlatformBundle\Model;

/**
 * Usage Stat model
 */
class UsageStat
{
    private string $title;

    private ?string $tooltip;

    private ?string $value;

    public static function create(string $title, ?string $tooltip = null, ?string $value = null): UsageStat
    {
        $usageStat = new static();

        $usageStat->title = $title;
        $usageStat->tooltip = $tooltip;
        $usageStat->value = $value;

        return $usageStat;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
