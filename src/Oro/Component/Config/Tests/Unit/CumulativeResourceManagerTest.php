<?php

namespace Oro\Component\Config\Tests\Unit;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;

class CumulativeResourceManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAndSetBundles()
    {
        CumulativeResourceManager::getInstance()->clear();

        self::assertCount(
            0,
            CumulativeResourceManager::getInstance()->getBundles()
        );

        CumulativeResourceManager::getInstance()->setBundles(['TestBundle1' => TestBundle1::class]);
        self::assertEquals(
            ['TestBundle1' => TestBundle1::class],
            CumulativeResourceManager::getInstance()->getBundles()
        );
    }

    public function testGetAndSetAppRootDir()
    {
        CumulativeResourceManager::getInstance()->clear();

        self::assertNull(
            CumulativeResourceManager::getInstance()->getAppRootDir()
        );

        $appDir = 'app_dir';
        CumulativeResourceManager::getInstance()->setAppRootDir($appDir);
        self::assertEquals(
            $appDir,
            CumulativeResourceManager::getInstance()->getAppRootDir()
        );
    }

    public function testClear()
    {
        CumulativeResourceManager::getInstance()->setAppRootDir('app_dir');
        CumulativeResourceManager::getInstance()->setBundles(['TestBundle1' => TestBundle1::class]);

        CumulativeResourceManager::getInstance()->clear();

        self::assertNull(
            CumulativeResourceManager::getInstance()->getAppRootDir()
        );
        self::assertCount(
            0,
            CumulativeResourceManager::getInstance()->getBundles()
        );
    }
}
