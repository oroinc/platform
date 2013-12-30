<?php
namespace Oro\Bundle\DistributionBundle\Tests\Unit\Manager\Helper;


use Composer\Package\PackageInterface;
use Oro\Bundle\DistributionBundle\Manager\Helper\ChangeSetBuilder;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\MockHelperTrait;

class ChangeSetBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructedWithoutArgs()
    {
        new ChangeSetBuilder();
    }

    /**
     * @test
     */
    public function shouldReturnInstalledUpdatedAndUninstalledPackages()
    {
        $package1 = $this->createPackageMock('name1', 'ref1');
        $package2 = $this->createPackageMock('name2', 'ref2');
        $package21 = $this->createPackageMock('name2', 'ref21');
        $package3 = $this->createPackageMock('name3', 'ref3');
        $package4 = $this->createPackageMock('name4', 'ref4');

        $previousInstalledPackages = [$package1, $package2, $package3];
        $currentlyInstalledPackages = [$package21, $package3, $package4];

        $expectedInstalled = [$package4];
        $expectedUpdated = [$package21];
        $expectedUninstalled = [$package1];

        $builder = new ChangeSetBuilder();
        list($actualInstalled, $actualUpdated, $actualUninstalled) = $builder->build(
            $previousInstalledPackages,
            $currentlyInstalledPackages
        );

        $this->assertSame($expectedInstalled, $actualInstalled);
        $this->assertSame($expectedUpdated, $actualUpdated);
        $this->assertSame($expectedUninstalled, $actualUninstalled);
    }

    /**
     * @param string $name
     * @param string $sourceReference
     * @return \PHPUnit_Framework_MockObject_MockObject|PackageInterface
     */
    protected function createPackageMock($name, $sourceReference)
    {
        $package = $this->getMock('Composer\Package\PackageInterface');
        $package->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $package->expects($this->any())
            ->method('getSourceReference')
            ->will($this->returnValue($sourceReference));

        return $package;

    }
}
