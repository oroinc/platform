<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Extension;

use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;

class DataStorageExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $dataStorage = new DataStorageExtension();
        $this->assertNull($dataStorage->get('test'));
        $this->assertFalse($dataStorage->get('test', false));

        $value = ['test1' => 'test1'];
        $dataStorage->set('test', $value);
        $this->assertSame($value, $dataStorage->get('test'));
    }

    public function testHas()
    {
        $dataStorage = new DataStorageExtension();
        $this->assertFalse($dataStorage->has('test'));

        $dataStorage->set('test', 'value');
        $this->assertTrue($dataStorage->has('test'));
    }

    public function testSetAndRemove()
    {
        $dataStorage = new DataStorageExtension();

        $dataStorage->remove('test');
        $this->assertFalse($dataStorage->has('test'));

        $dataStorage->set('test', 'value');
        $this->assertTrue($dataStorage->has('test'));
        $dataStorage->remove('test');
        $this->assertFalse($dataStorage->has('test'));
    }
}
