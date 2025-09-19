<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures;

use Attribute;
use Oro\Bundle\PlatformBundle\Interface\PHPAttributeConfigurationInterface;

/**
 * Test attribute that allows arrays
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class TestArrayAttribute implements PHPAttributeConfigurationInterface
{
    public function __construct(
        private string $name = 'default_name',
        private mixed $data = null
    ) {
    }

    public function getAliasName(): string
    {
        return 'test_array_attribute';
    }

    public function allowArray(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
