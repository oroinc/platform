<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider;

use Composer\Package\Package;
use Oro\Bundle\PlatformBundle\Composer\LocalRepositoryFactory;
use Oro\Bundle\PlatformBundle\Provider\PackageProvider;

class PackageProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PackageProvider */
    protected $provider;

    /** @var LocalRepositoryFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $factory;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder('Oro\Bundle\PlatformBundle\Composer\LocalRepositoryFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PackageProvider($this->factory);
    }

    public function testGetThirdPartyPackagesEmpty()
    {
        $packages = [new Package('oro/platform', '1.0.0', '1.0.0')];

        $repository = $this->createMock('Composer\Repository\InstalledRepositoryInterface');
        $repository->expects($this->once())->method('getCanonicalPackages')->willReturn($packages);
        $this->factory->expects($this->once())->method('getLocalRepository')->willReturn($repository);

        $this->assertEmpty($this->provider->getThirdPartyPackages());
    }

    public function testGetThirdPartyPackages()
    {
        $thirdPartyPackage = new Package('not-oro/platform', '1.0.0', '1.0.0');
        $packages = [new Package('oro/platform', '1.0.0', '1.0.0'), $thirdPartyPackage];

        $repository = $this->createMock('Composer\Repository\InstalledRepositoryInterface');
        $repository->expects($this->once())->method('getCanonicalPackages')->willReturn($packages);
        $this->factory->expects($this->once())->method('getLocalRepository')->willReturn($repository);

        $this->assertEquals(
            [$thirdPartyPackage->getPrettyName() => $thirdPartyPackage],
            $this->provider->getThirdPartyPackages()
        );
    }

    public function testGetOroPackagesEmpty()
    {
        $packages = [new Package('not-oro/platform', '1.0.0', '1.0.0')];

        $repository = $this->createMock('Composer\Repository\InstalledRepositoryInterface');
        $repository->expects($this->once())->method('getCanonicalPackages')->willReturn($packages);
        $this->factory->expects($this->once())->method('getLocalRepository')->willReturn($repository);

        $this->assertEmpty($this->provider->getOroPackages());
    }

    public function testGetOroPackages()
    {
        $thirdPartyPackage = new Package('not-oro/platform', '1.0.0', '1.0.0');
        $oroPackage = new Package('oro/platform', '1.0.0', '1.0.0');
        $packages = [$oroPackage, $thirdPartyPackage];

        $repository = $this->createMock('Composer\Repository\InstalledRepositoryInterface');
        $repository->expects($this->once())->method('getCanonicalPackages')->willReturn($packages);
        $this->factory->expects($this->once())->method('getLocalRepository')->willReturn($repository);

        $this->assertEquals(
            [$oroPackage->getPrettyName() => $oroPackage],
            $this->provider->getOroPackages()
        );
    }

    public function testFilterOroPackages()
    {
        $oroPackage = new Package('oro/platform', '1.0.0', '1.0.0');
        $oroExtension = new Package('oro/some-extension', '1.0.0', '1.0.0');
        $packages = [$oroPackage, $oroExtension];

        $repository = $this->createMock('Composer\Repository\InstalledRepositoryInterface');
        $repository->expects($this->atLeastOnce())->method('getCanonicalPackages')->willReturn($packages);
        $this->factory->expects($this->atLeastOnce())->method('getLocalRepository')->willReturn($repository);

        $this->assertEquals(
            [$oroPackage->getPrettyName() => $oroPackage],
            $this->provider->getOroPackages(false)
        );

        $this->assertEquals(
            [$oroPackage->getPrettyName() => $oroPackage, $oroExtension->getPrettyName() => $oroExtension],
            $this->provider->getOroPackages()
        );

        $this->assertEmpty($this->provider->getThirdPartyPackages());
    }
}
