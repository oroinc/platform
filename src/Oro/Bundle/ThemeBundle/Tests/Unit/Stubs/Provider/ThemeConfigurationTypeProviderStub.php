<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Stubs\Provider;

use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationTypeProviderInterface;

class ThemeConfigurationTypeProviderStub implements ThemeConfigurationTypeProviderInterface
{
    public function __construct(
        private string $type,
        private string $label
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
