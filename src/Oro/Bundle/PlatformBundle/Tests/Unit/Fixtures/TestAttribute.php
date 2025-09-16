<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures;

use Attribute;
use Oro\Bundle\PlatformBundle\Interface\PHPAttributeConfigurationInterface;

/**
 * Test attribute that implements PHPAttributeConfigurationInterface
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class TestAttribute implements PHPAttributeConfigurationInterface
{
    public function __construct(
        private string $value = 'default',
        private bool $enabled = true
    ) {
    }

    public function getAliasName(): string
    {
        return 'test_attribute';
    }

    public function allowArray(): bool
    {
        return false;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
