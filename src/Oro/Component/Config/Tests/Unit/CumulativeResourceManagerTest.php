<?php

namespace Oro\Component\Config\Tests\Unit;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;

class CumulativeResourceManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAndSetBundles()
    {
        CumulativeResourceManager::getInstance()->clear();

        $this->assertCount(
            0,
            CumulativeResourceManager::getInstance()->getBundles()
        );

        $bundle = new TestBundle1();
        CumulativeResourceManager::getInstance()->setBundles(['TestBundle1' => get_class($bundle)]);
        $this->assertEquals(
            ['TestBundle1' => get_class($bundle)],
            CumulativeResourceManager::getInstance()->getBundles()
        );
    }
}
