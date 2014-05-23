<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Composer;

use Oro\Bundle\PlatformBundle\Composer\VersionHelper;

class VersionHelperTest extends \PHPUnit_Framework_TestCase
{
    const VERSION = '1.0';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repo;

    protected function setUp()
    {
        $this->factory = $this
            ->getMockBuilder('Oro\Bundle\PlatformBundle\Composer\LocalRepositoryFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repo = $this->getMock('Composer\Repository\WritableRepositoryInterface');
    }

    public function testGetVersion()
    {
        $package = $this
            ->getMock('Composer\Package\PackageInterface');

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

        $helper = new VersionHelper($this->factory);

        $this->assertEquals(self::VERSION, $helper->getVersion());
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

        $helper = new VersionHelper($this->factory);

        $this->assertEquals(VersionHelper::UNDEFINED_VERSION, $helper->getVersion());
    }
}
