<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Script;


use Oro\Bundle\DistributionBundle\Script\Runner;
use Composer\Package\PackageInterface;

class RunnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructedWithInstallationManager()
    {
        new Runner($this->createInstallationManagerMock());
    }

    /**
     * @test
     */
    public function shouldRunValidInstallScriptOfPackageAndReturnOutput()
    {
        $package = $this->createPackageMock();
        $runner = new Runner($this->createInstallationManagerMock($package, __DIR__ . '/../Fixture/Script/valid'));
        $this->assertEquals('The install script was executed', $runner->install($package));
    }

    /**
     * @test
     */
    public function shouldDoNothingWhenInstallScriptIsAbsent()
    {
        $package = $this->createPackageMock();
        $runner = new Runner($this->createInstallationManagerMock($package, __DIR__ . '/../Fixture/Script/empty'));
        $this->assertNull($runner->install($package));
    }

    /**
     * @test
     *
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     * @expectedException Exit Code: 255(Unknown error)
     */
    public function throwExceptionWhenProcessFailed()
    {
        $package = $this->createPackageMock();
        $runner = new Runner($this->createInstallationManagerMock($package, __DIR__ . '/../Fixture/Script/invalid'));
        $runner->install($package);
    }

    /**
     * @test
     */
    public function shouldRunValidUninstallScriptOfPackageAndReturnOutput()
    {
        $package = $this->createPackageMock();
        $runner = new Runner($this->createInstallationManagerMock($package, __DIR__ . '/../Fixture/Script/valid'));
        $this->assertEquals('The uninstall script was executed', $runner->uninstall($package));
    }

    /**
     * @test
     */
    public function shouldDoNothingWhenUninstallScriptIsAbsent()
    {
        $package = $this->createPackageMock();
        $runner = new Runner($this->createInstallationManagerMock($package, __DIR__ . '/../Fixture/Script/empty'));
        $this->assertNull($runner->install($package));
    }

    /**
     * @test
     *
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     * @expectedException Exit Code: 255(Unknown error)
     */
    public function throwExceptionWhenProcessFailedDuringUninstalling()
    {
        $package = $this->createPackageMock();
        $runner = new Runner($this->createInstallationManagerMock($package, __DIR__ . '/../Fixture/Script/invalid'));
        $runner->uninstall($package);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createPackageMock()
    {
        return $this->getMock('Composer\Package\PackageInterface');
    }

    /**
     * @param \Composer\Package\PackageInterface $package
     * @param string $targetDir
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createInstallationManagerMock(PackageInterface $package = null, $targetDir = null)
    {
        $im = $this->getMock('Composer\Installer\InstallationManager');
        if ($package) {
            $im->expects($this->any())
                ->method('getInstallPath')
                ->with($package)
                ->will($this->returnValue($targetDir));
        }

        return $im;
    }
}
