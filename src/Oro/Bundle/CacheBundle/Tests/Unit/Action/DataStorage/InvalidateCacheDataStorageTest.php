<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Action\DataStorage;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;

class InvalidateCacheDataStorageTest extends \PHPUnit\Framework\TestCase
{
    public function testCompiles()
    {
        $storage = new InvalidateCacheDataStorage();

        $storage->set('test', 145);

        static::assertSame(145, $storage->get('test'));
    }
}
