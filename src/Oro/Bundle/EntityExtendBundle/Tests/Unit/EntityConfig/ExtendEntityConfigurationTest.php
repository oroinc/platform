<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\ConfigurationHandler;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendEntityConfiguration;
use PHPUnit\Framework\TestCase;

class ExtendEntityConfigurationTest extends TestCase
{
    private ConfigurationHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ConfigurationHandler(new \ArrayIterator([new ExtendEntityConfiguration()]));
    }

    public function testUniqueKeyAcceptsArray(): void
    {
        $uniqueKeyData = [
            ['name' => 'test_key', 'key' => ['field1', 'field2']],
            ['name' => 'another_key', 'key' => ['field3']],
        ];

        $result = $this->handler->process(
            ConfigurationHandler::CONFIG_ENTITY_TYPE,
            'extend',
            ['unique_key' => $uniqueKeyData],
            'Foo\Bar\Entity'
        );

        $this->assertEquals($uniqueKeyData, $result['unique_key']);
    }

    public function testUniqueKeyAcceptsNull(): void
    {
        $result = $this->handler->process(
            ConfigurationHandler::CONFIG_ENTITY_TYPE,
            'extend',
            ['unique_key' => null],
            'Foo\Bar\Entity'
        );

        $this->assertNull($result['unique_key']);
    }

    public function testUniqueKeyAcceptsString(): void
    {
        $result = $this->handler->process(
            ConfigurationHandler::CONFIG_ENTITY_TYPE,
            'extend',
            ['unique_key' => 'some_legacy_value'],
            'Foo\Bar\Entity'
        );

        $this->assertEquals('some_legacy_value', $result['unique_key']);
    }
}
