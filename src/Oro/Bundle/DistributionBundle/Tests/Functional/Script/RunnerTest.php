<?php
namespace Oro\Bundle\DistributionBundle\Tests\Functional\Script;

use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Psr\Log\LoggerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\DistributionBundle\Script\Runner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RunnerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $applicationRootDir;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->applicationRootDir = $this->client->getKernel()->getRootDir();
        if (!is_dir($this->applicationRootDir . '/config/dist')) {
            $this->markTestSkipped('Distribution tests are not compatibility with CRM environment');
        }
    }

    public function testShouldBeConstructedWithInstallationManager()
    {
        new Runner($this->createInstallationManagerMock(), $this->createLoggerMock(
        ), 'path/to/application/root/dir', 'test');
    }

    public function testShouldRunValidInstallScriptOfPackageAndReturnOutput()
    {
        $package = $this->createPackageMock();
        $targetDir = __DIR__ . '/../Fixture/Script/valid';
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(2))
            ->method('info');

        $runner = $this->createRunner($package, $logger, $targetDir);
        $expectedOutput = $this->formatExpectedResult(
            'Valid install script',
            $targetDir . '/install.php',
            'The install script was executed'
        );

        $this->assertEquals($expectedOutput, $runner->runInstallScripts($package));
    }

    public function testShouldDoNothingWhenInstallScriptIsAbsent()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('There is no '));
        $runner = $this->createRunner($package, $logger, __DIR__ . '/../Fixture/Script/empty');

        $this->assertNull($runner->runInstallScripts($package));
    }

    /**
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     * @expectedExceptionMessage The command
     */
    public function testThrowExceptionWhenProcessFailed()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The command '));
        $runner = $this->createRunner($package, $logger, __DIR__ . '/../Fixture/Script/invalid');

        $runner->runInstallScripts($package);
    }

    public function testShouldRunValidUninstallScriptOfPackageAndReturnOutput()
    {
        $package = $this->createPackageMock();
        $targetDir = __DIR__ . '/../Fixture/Script/valid';
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(2))
            ->method('info');
        $runner = $this->createRunner($package, $logger, $targetDir);
        $expectedOutput = $this->formatExpectedResult(
            'Valid uninstall script',
            $targetDir . '/uninstall.php',
            'The uninstall script was executed'
        );

        $this->assertEquals($expectedOutput, $runner->runUninstallScripts($package));
    }

    public function testShouldDoNothingWhenUninstallScriptIsAbsent()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('There is no '));
        $runner = $this->createRunner($package, $logger, __DIR__ . '/../Fixture/Script/empty');

        $this->assertNull($runner->runUninstallScripts($package));
    }

    /**
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     * @expectedExceptionMessage The command
     */
    public function testThrowExceptionWhenProcessFailedDuringUninstalling()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The command '));
        $runner = $this->createRunner($package, $logger, __DIR__ . '/../Fixture/Script/invalid');

        $runner->runUninstallScripts($package);
    }

    public function testShouldRunValidUpdateScriptOfPackageAndReturnOutput()
    {
        $package = $this->createPackageMock();
        $targetDir = __DIR__ . '/../Fixture/Script/valid';
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(2))
            ->method('info');
        $runner = $this->createRunner($package, $logger, $targetDir);
        $expectedOutput = $this->formatExpectedResult(
            'Valid update script',
            $targetDir . '/update.php',
            'The update script was executed'
        );

        $this->assertEquals($expectedOutput, $runner->update($package, 'any version'));
    }

    public function testShouldDoNothingWhenUpdateScriptIsAbsent()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('There is no '));
        $runner = $this->createRunner($package, $logger, __DIR__ . '/../Fixture/Script/empty');

        $this->assertNull($runner->update($package, 'any version'));
    }

    /**
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     * @expectedExceptionMessage The command
     */
    public function testThrowExceptionWhenProcessFailedDuringUpdating()
    {
        $package = $this->createPackageMock();
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The command '));
        $runner = $this->createRunner($package, $logger, __DIR__ . '/../Fixture/Script/invalid');

        $runner->update($package, 'any version');
    }

    public function testShouldRunMigrationScriptsUpToCurrentPackageVersionSimple()
    {
        $package = $this->createPackageMock();
        $targetDir = __DIR__ . '/../Fixture/Script/valid/update-migrations/simple';
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(4))
            ->method('info');
        $runner = $this->createRunner($package, $logger, $targetDir);
        $expectedRunnerOutput = $this->formatExpectedResult(
            'Simple migration 2 script',
            $targetDir . DIRECTORY_SEPARATOR . 'update_2.php',
            'update 2'
        );
        $expectedRunnerOutput .= PHP_EOL;
        $expectedRunnerOutput .= $this->formatExpectedResult(
            'Simple migration 3 script',
            $targetDir . DIRECTORY_SEPARATOR . 'update_3.php',
            'update 3'
        );

        $this->assertEquals($expectedRunnerOutput, $runner->update($package, '1'));
    }

    public function testShouldRunMigrationScriptsUpToCurrentPackageVersionComplex()
    {
        $package = $this->createPackageMock();
        $targetDir = __DIR__ . '/../Fixture/Script/valid/update-migrations/complex';
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(4))
            ->method('info');
        $runner = $this->createRunner($package, $logger, $targetDir);
        $expectedRunnerOutput = $this->formatExpectedResult(
            'Complex migration 0_1_9_1 script',
            $targetDir . DIRECTORY_SEPARATOR . 'update_0.1.9.1.php',
            'update 0.1.9.1'
        );
        $expectedRunnerOutput .= PHP_EOL;
        $expectedRunnerOutput .= $this->formatExpectedResult(
            'Complex migration 0_1_10 script',
            $targetDir . DIRECTORY_SEPARATOR . 'update_0.1.10.php',
            'update 0.1.10'
        );

        $this->assertEquals($expectedRunnerOutput, $runner->update($package, '0.1.9'));
    }

    public function testShouldRunUpdatePlatformCommandWithoutErrors()
    {
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(2))
            ->method('info');
        $runner = $this->createRunner(null, $logger);

        $runner->runPlatformUpdate();
    }

    public function testShouldRemoveCachedFiles()
    {
        $tempDir = sys_get_temp_dir() . '/platform-app-tmp';
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
        mkdir($tempDir);

        $bundlesFileName = $tempDir . '/bundles.php';
        $containerFileName = $tempDir . '/' . uniqid() . 'ProjectContainer.php';
        touch($bundlesFileName);
        touch($containerFileName);

        //guard
        $this->assertFileExists($bundlesFileName);
        $this->assertFileExists($containerFileName);

        $runner = $this->createRunner(null, null, null, $tempDir);
        $runner->removeCachedFiles();

        $this->assertFileNotExists($bundlesFileName);
        $this->assertFileNotExists($containerFileName);
    }

    public function testShouldRunClearCacheCommandForDistApplicationWithoutErrors()
    {
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(2))
            ->method('info');
        $runner = $this->createRunner(null, $logger);

        $runner->clearDistApplicationCache();
    }

    public function testShouldRunClearCacheCommandWithoutErrors()
    {
        $logger = $this->createLoggerMock();
        $logger->expects($this->exactly(2))
            ->method('info');
        $runner = $this->createRunner(null, $logger);

        $runner->clearApplicationCache();
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
        $format = 'Launching "%s" (%s) script' . PHP_EOL
            . '%s' . PHP_EOL;

        return sprintf($format, $annotationLabel, $pathToFile, $scriptOutput);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerMock()
    {
        return $this->getMock('Psr\Log\LoggerInterface');
    }

    /**
     * @param PackageInterface $package
     * @param LoggerInterface $logger
     * @param string $targetDir
     * @param string $applicationRootDir
     *
     * @return Runner
     */
    protected function createRunner(
        PackageInterface $package = null,
        LoggerInterface $logger = null,
        $targetDir = null,
        $applicationRootDir = null
    ) {
        return new Runner(
            $this->createInstallationManagerMock($package, $targetDir),
            $logger ? : $this->createLoggerMock(),
            $applicationRootDir ? $applicationRootDir : $this->applicationRootDir,
            'test'
        );
    }
}
