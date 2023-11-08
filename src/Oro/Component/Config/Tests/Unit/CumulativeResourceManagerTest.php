<?php

namespace Oro\Component\Config\Tests\Unit;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;

class CumulativeResourceManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAndSetBundles(): void
    {
        CumulativeResourceManager::getInstance()->clear();

        self::assertCount(0, CumulativeResourceManager::getInstance()->getBundles());

        CumulativeResourceManager::getInstance()->setBundles(['TestBundle1' => TestBundle1::class]);
        self::assertEquals(
            ['TestBundle1' => TestBundle1::class],
            CumulativeResourceManager::getInstance()->getBundles()
        );
    }

    public function testGetAndSetAppRootDir(): void
    {
        CumulativeResourceManager::getInstance()->clear();

        self::assertNull(CumulativeResourceManager::getInstance()->getAppRootDir());

        CumulativeResourceManager::getInstance()->setAppRootDir(__DIR__);
        self::assertEquals(
            __DIR__,
            CumulativeResourceManager::getInstance()->getAppRootDir()
        );
    }

    public function testGetBundleAppDir(): void
    {
        CumulativeResourceManager::getInstance()->clear();

        CumulativeResourceManager::getInstance()->setAppRootDir(__DIR__);
        CumulativeResourceManager::getInstance()->setBundles(['TestBundle1' => TestBundle1::class]);

        self::assertEquals(
            __DIR__ . '/Resources/TestBundle1',
            CumulativeResourceManager::getInstance()->getBundleAppDir('TestBundle1')
        );
    }

    public function testGetBundleAppDirWhenAppRootDirNotExists(): void
    {
        CumulativeResourceManager::getInstance()->clear();

        CumulativeResourceManager::getInstance()->setAppRootDir(__DIR__ . 'NotExisting');
        CumulativeResourceManager::getInstance()->setBundles(['TestBundle1' => TestBundle1::class]);

        self::assertEquals(
            '',
            CumulativeResourceManager::getInstance()->getBundleAppDir('TestBundle1')
        );
    }

    public function testGetBundleAppDirWhenAppRootDirNotSet(): void
    {
        CumulativeResourceManager::getInstance()->clear();

        CumulativeResourceManager::getInstance()->setBundles(['TestBundle1' => TestBundle1::class]);

        self::assertEquals(
            '',
            CumulativeResourceManager::getInstance()->getBundleAppDir('TestBundle1')
        );
    }

    public function testClear(): void
    {
        CumulativeResourceManager::getInstance()->setAppRootDir(__DIR__);
        CumulativeResourceManager::getInstance()->setBundles(['TestBundle1' => TestBundle1::class]);

        CumulativeResourceManager::getInstance()->clear();

        self::assertNull(CumulativeResourceManager::getInstance()->getAppRootDir());
        self::assertCount(0, CumulativeResourceManager::getInstance()->getBundles());
    }


    public function testInitializer(): void
    {
        CumulativeResourceManager::getInstance()->clear();

        CumulativeResourceManager::getInstance()
            ->setInitializer(function (CumulativeResourceManager $manager): void {
                $manager->setAppRootDir(__DIR__);
                $manager->setBundles(['TestBundle1' => TestBundle1::class]);
            });

        self::assertEquals(
            __DIR__,
            CumulativeResourceManager::getInstance()->getAppRootDir()
        );
        self::assertEquals(
            ['TestBundle1' => TestBundle1::class],
            CumulativeResourceManager::getInstance()->getBundles()
        );
    }
}
