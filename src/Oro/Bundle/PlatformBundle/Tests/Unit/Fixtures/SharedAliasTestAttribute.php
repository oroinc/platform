<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures;

use Attribute;
use Oro\Bundle\PlatformBundle\Interface\PHPAttributeConfigurationInterface;

/**
 * Special test attribute that has a shared alias but different allowArray behavior
 * Used specifically for testing array/single mismatch scenarios
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class SharedAliasTestAttribute implements PHPAttributeConfigurationInterface
{
    public function __construct(
        private string $value,
        private bool $allowsArray = false
    ) {
    }

    public function getAliasName(): string
    {
        // ВАЖНО: одинаковый alias для создания конфликта
        return 'shared_alias_test';
    }

    public function allowArray(): bool
    {
        return $this->allowsArray;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
