<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Extension;

use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;

class DataStorageExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $dataStorage = new DataStorageExtension();
        $dataStorage->put('test', ['test1' => 'test1']);

        $this->assertEquals(
            $dataStorage->get('test'),
            ['test1' => 'test1']
        );

        $this->assertTrue($dataStorage->has('test'));
    }

    public function testHas()
    {
        $dataStorage = new DataStorageExtension();
        $dataStorage->put('test', ['test1' => 'test1']);

        $this->assertTrue($dataStorage->has('test'));
    }
}
