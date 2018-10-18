<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Composer;

use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\PlatformBundle\OroPlatformBundle;

class VersionHelperTest extends \PHPUnit\Framework\TestCase
{
    const VERSION = '1.0';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $factory;

    /**
     * @var VersionHelper
     */
    protected $helper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $repo;

    protected function setUp()
    {
        $this->factory = $this
            ->getMockBuilder('Oro\Bundle\PlatformBundle\Composer\LocalRepositoryFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repo = $this->createMock('Composer\Repository\WritableRepositoryInterface');
        $this->helper = new VersionHelper($this->factory);
    }

    /**
     * @dataProvider hasCacheDataProvider
     * @param bool $hasCache
     */
    public function testGetVersion($hasCache)
    {
        if ($hasCache) {
            $cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
                ->disableOriginalConstructor()
                ->setMethods(array('save'))
                ->getMockForAbstractClass();

            $cache->expects($this->once())
                ->method('save')
                ->with(OroPlatformBundle::PACKAGE_NAME, self::VERSION);

            $this->helper->setCache($cache);
        }

        $package = $this
            ->createMock('Composer\Package\PackageInterface');

        $package
            ->expects($this->once())
            ->method('getPrettyVersion')
            ->will($this->returnValue(self::VERSION));

        $this->repo
            ->expects($this->once())
            ->method('findPackages')
            ->will($this->returnValue([$package]));

        $this->factory
            ->expects($this->once())
            ->method('getLocalRepository')
            ->will($this->returnValue($this->repo));

        $this->assertEquals(self::VERSION, $this->helper->getVersion());
        // Check that local cache used
        $this->assertEquals(self::VERSION, $this->helper->getVersion());
    }

    public function hasCacheDataProvider()
    {
        return array(
            array(false),
            array(true)
        );
    }

    public function testGetVersionNotAvailable()
    {
        $this->repo
            ->expects($this->once())
            ->method('findPackages')
            ->will($this->returnValue([]));

        $this->factory
            ->expects($this->once())
            ->method('getLocalRepository')
            ->will($this->returnValue($this->repo));

        $this->assertEquals(VersionHelper::UNDEFINED_VERSION, $this->helper->getVersion());
    }

    public function testGetVersionCached()
    {
        $cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->disableOriginalConstructor()
            ->setMethods(array('save', 'contains', 'fetch'))
            ->getMockForAbstractClass();

        $cache->expects($this->once())
            ->method('contains')
            ->with(OroPlatformBundle::PACKAGE_NAME)
            ->will($this->returnValue(true));
        $cache->expects($this->once())
            ->method('fetch')
            ->with(OroPlatformBundle::PACKAGE_NAME)
            ->will($this->returnValue('1.1'));
        $cache->expects($this->never())
            ->method('save');

        $this->helper->setCache($cache);
        $this->assertEquals('1.1', $this->helper->getVersion());
    }
}
