<?php
namespace Oro\Bundle\DistributionBundle\Tests\Functional\Script;

use Composer\Installer\InstallationManager;
use Oro\Bundle\DistributionBundle\Script\Runner;
use Composer\Package\PackageInterface;
use Psr\Log\LoggerInterface;

class RunnerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function shouldBeConstructedWithInstallationManager()
    {
        new Runner($this->createInstallationManagerMock(), $this->createLoggerMock(), 'path/to/application/root/dir');
    }

    /**
     * @test
     */
    public function shouldRunValidInstallScriptOfPackageAndReturnOutput()
    {
        $package = $this->createPackageMock();
        $targetDir = __DIR__ . '/../Fixture/Script/valid';
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(2))
            ->method('info');

        $runner = $this->createRunner($package, $targetDir, $logger);
        $expectedOutput = $this->formatExpectedResult(
            'Valid install script',
            $targetDir . '/install.php',
            'The install script was executed'
        );

        $this->assertEquals($expectedOutput, $runner->install($package));
    }

    /**
     * @test
     */
    public function shouldDoNothingWhenInstallScriptIsAbsent()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('There is no '));
        $runner = $this->createRunner($package, __DIR__ . '/../Fixture/Script/empty', $logger);

        $this->assertNull($runner->install($package));
    }

    /**
     * @test
     *
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     * @expectedExceptionMessage The command
     */
    public function throwExceptionWhenProcessFailed()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The command '));
        $runner = $this->createRunner($package, __DIR__ . '/../Fixture/Script/invalid', $logger);

        $runner->install($package);
    }

    /**
     * @test
     */
    public function shouldRunValidUninstallScriptOfPackageAndReturnOutput()
    {
        $package = $this->createPackageMock();
        $targetDir = __DIR__ . '/../Fixture/Script/valid';
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(2))
            ->method('info');
        $runner = $this->createRunner($package, $targetDir, $logger);
        $expectedOutput = $this->formatExpectedResult(
            'Valid uninstall script',
            $targetDir . '/uninstall.php',
            'The uninstall script was executed'
        );

        $this->assertEquals($expectedOutput, $runner->uninstall($package));
    }

    /**
     * @test
     */
    public function shouldDoNothingWhenUninstallScriptIsAbsent()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('There is no '));
        $runner = $this->createRunner($package, __DIR__ . '/../Fixture/Script/empty', $logger);

        $this->assertNull($runner->uninstall($package));
    }

    /**
     * @test
     *
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     * @expectedExceptionMessage The command
     */
    public function throwExceptionWhenProcessFailedDuringUninstalling()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The command '));
        $runner = $this->createRunner($package, __DIR__ . '/../Fixture/Script/invalid', $logger);

        $runner->uninstall($package);
    }

    /**
     * @test
     */
    public function shouldRunValidUpdateScriptOfPackageAndReturnOutput()
    {
        $package = $this->createPackageMock();
        $targetDir = __DIR__ . '/../Fixture/Script/valid';
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(2))
            ->method('info');
        $runner = $this->createRunner($package, $targetDir, $logger);
        $expectedOutput = $this->formatExpectedResult(
            'Valid update script',
            $targetDir . '/update.php',
            'The update script was executed'
        );

        $this->assertEquals($expectedOutput, $runner->update($package, 'any version'));
    }

    /**
     * @test
     */
    public function shouldDoNothingWhenUpdateScriptIsAbsent()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('There is no '));
        $runner = $this->createRunner($package, __DIR__ . '/../Fixture/Script/empty', $logger);

        $this->assertNull($runner->update($package, 'any version'));
    }

    /**
     * @test
     *
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     * @expectedExceptionMessage The command
     */
    public function throwExceptionWhenProcessFailedDuringUpdating()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The command '));
        $runner = $this->createRunner($package, __DIR__ . '/../Fixture/Script/invalid', $logger);

        $runner->update($package, 'any version');
    }

    /**
     * @test
     */
    public function shouldRunMigrationScriptsUpToCurrentPackageVersionSimple()
    {
        $package = $this->createPackageMock();
        $targetDir = __DIR__ . '/../Fixture/Script/valid/update-migrations/simple';
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(4))
            ->method('info');
        $runner = $this->createRunner($package, $targetDir, $logger);
        $expectedRunnerOutput = $this->formatExpectedResult(
            'Simple migration 2 script',
            $targetDir . '/update_2.php',
            'update 2'
        ) . PHP_EOL . $this->formatExpectedResult(
            'Simple migration 3 script',
            $targetDir . '/update_3.php',
            'update 3'
        );

        $this->assertEquals($expectedRunnerOutput, $runner->update($package, '1'));
    }

    /**
     * @test
     */
    public function shouldRunMigrationScriptsUpToCurrentPackageVersionComplex()
    {
        $package = $this->createPackageMock();
        $targetDir = __DIR__ . '/../Fixture/Script/valid/update-migrations/complex';
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(4))
            ->method('info');
        $runner = $this->createRunner($package, $targetDir, $logger);
        $expectedRunnerOutput = $this->formatExpectedResult(
            'Complex migration 0_1_9_1 script',
            $targetDir . '/update_0.1.9.1.php',
            'update 0.1.9.1'
        ) . PHP_EOL . $this->formatExpectedResult(
            'Complex migration 0_1_10 script',
            $targetDir . '/update_0.1.10.php',
            'update 0.1.10'
        );

        $this->assertEquals($expectedRunnerOutput, $runner->update($package, '0.1.9'));
    }

    /**
     * @test
     */
    public function shouldRunUpdatePlatformCommandWithoutErrors()
    {
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(2))
            ->method('info');
        $runner = $this->createRunner(null, null, $logger);

        $runner->runPlatformUpdate();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PackageInterface
     */
    protected function createPackageMock()
    {
        return $this->getMock('Composer\Package\PackageInterface');
    }

    /**
     * @param \Composer\Package\PackageInterface $package
     * @param string $targetDir
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|InstallationManager
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

    /**
     * @param $annotationLabel
     * @param $pathToFile
     * @param $scriptOutput
     *
     * @return string
     */
    protected function formatExpectedResult($annotationLabel, $pathToFile, $scriptOutput)
    {
        $format = <<<OUTPUT
Launching "%s" (%s) script
%s

OUTPUT;

        return sprintf($format, $annotationLabel, $pathToFile, $scriptOutput);
    }

    /**
     * @param PackageInterface $package
     * @param string $targetDir
     * @param LoggerInterface $logger
     *
     * @return Runner
     */
    protected function createRunner(PackageInterface $package = null, $targetDir = null, LoggerInterface $logger=null)
    {
        if (!$logger) {
            $logger = $this->createLoggerMock();
        }
        return new Runner($this->createInstallationManagerMock($package, $targetDir), $logger, '.');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerMock()
    {
        return $this->getMock('Psr\Log\LoggerInterface');
    }
}
