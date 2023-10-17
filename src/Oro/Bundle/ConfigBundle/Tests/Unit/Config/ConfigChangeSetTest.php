<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;

class ConfigChangeSetTest extends \PHPUnit\Framework\TestCase
{
    public function testGetChanges(): void
    {
        $changes = [
            'item1' => [
                'old' => 'old value',
                'new' => 'new value'
            ]
        ];

        $configChangeSet = new ConfigChangeSet($changes);
        $this->assertEquals($changes, $configChangeSet->getChanges());
    }

    public function testIsChangedForChangedValue(): void
    {
        $configChangeSet = new ConfigChangeSet(
            [
                'item1' => [
                    'old' => 'old value',
                    'new' => 'new value'
                ]
            ]
        );
        $this->assertTrue($configChangeSet->isChanged('item1'));
    }

    public function testIsChangedForNotChangedValue(): void
    {
        $configChangeSet = new ConfigChangeSet(
            [
                'item1' => [
                    'old' => 'old value',
                    'new' => 'new value'
                ]
            ]
        );
        $this->assertFalse($configChangeSet->isChanged('unknown'));
    }

    public function testGetNewValue(): void
    {
        $configChangeSet = new ConfigChangeSet(
            [
                'item1' => [
                    'old' => 'old value',
                    'new' => 'new value'
                ]
            ]
        );
        $this->assertEquals('new value', $configChangeSet->getNewValue('item1'));
    }

    public function testGetOldValue(): void
    {
        $configChangeSet = new ConfigChangeSet(
            [
                'item1' => [
                    'old' => 'old value',
                    'new' => 'new value'
                ]
            ]
        );
        $this->assertEquals('old value', $configChangeSet->getOldValue('item1'));
    }

    public function testGetNewValueForUnknownOption(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Could not retrieve a value for "unknown".');

        $configChangeSet = new ConfigChangeSet([]);
        $configChangeSet->getNewValue('unknown');
    }

    public function testGetOldValueForUnknownOption(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Could not retrieve a value for "unknown".');

        $configChangeSet = new ConfigChangeSet([]);
        $configChangeSet->getOldValue('unknown');
    }
}
