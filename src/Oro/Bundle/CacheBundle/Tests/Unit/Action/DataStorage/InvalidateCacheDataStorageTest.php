<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Action\DataStorage;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use PHPUnit\Framework\TestCase;

class InvalidateCacheDataStorageTest extends TestCase
{
    public function testCompiles(): void
    {
        $storage = new InvalidateCacheDataStorage();

        $storage->set('test', 145);

        self::assertSame(145, $storage->get('test'));
    }
}
