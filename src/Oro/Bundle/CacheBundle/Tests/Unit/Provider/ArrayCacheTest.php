<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Provider\ArrayCache;

class ArrayCacheTest extends \PHPUnit\Framework\TestCase
{
    const CACHE_KEY = 'datetime_key';

    public function testSaveCachedValueIsNotModifiedIfOriginalObjectIsModified()
    {
        $dateTime = new \DateTime('14-03-2016');
        $cache = new ArrayCache();
        $cache->save(self::CACHE_KEY, $dateTime);

        $dateTime->setDate(2000, 01, 01);

        $this->assertTrue($cache->contains(self::CACHE_KEY));
        $cachedDatetime = $cache->fetch(self::CACHE_KEY);

        $this->assertEquals('01-01-2000', $dateTime->format('d-m-Y'));
        $this->assertEquals('14-03-2016', $cachedDatetime->format('d-m-Y'));
    }
}
