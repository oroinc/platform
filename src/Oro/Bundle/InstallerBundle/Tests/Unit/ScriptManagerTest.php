<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit;

use Oro\Bundle\InstallerBundle\ScriptManager;
use Oro\Bundle\InstallerBundle\Tests\Unit\Fixture\src\TestPackage\src\Test1Bundle\TestPackageTest1Bundle;
use Oro\Bundle\InstallerBundle\Tests\Unit\Fixture\src\TestPackage\src\Test2Bundle\TestPackageTest2Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

class ScriptManagerTest extends \PHPUnit\Framework\TestCase
{
    private ScriptManager $scriptManager;

    protected function setUp(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::any())
            ->method('getProjectDir')
            ->willReturn(__DIR__ . '/Fixture/app');

        $bundles = [
            new TestPackageTest1Bundle(),
            new TestPackageTest2Bundle(),
        ];

        $kernel->expects(self::any())
            ->method('getBundles')
            ->willReturn($bundles);

        $this->scriptManager = new ScriptManager($kernel);
    }

    public function testGetScriptFiles(): void
    {
        $scriptFiles = $this->scriptManager->getScriptFiles();
        self::assertCount(3, $scriptFiles);

        $files = [
            '/Fixture/src/TestPackage/src/Test1Bundle/install.php',
            '/Fixture/src/TestPackage/src/Test2Bundle/install.php',
            '/Fixture/src/TestPackage/install.php'
        ];
        $i = 0;
        foreach ($scriptFiles as $scriptFile) {
            $scriptFile = str_replace([__DIR__, DIRECTORY_SEPARATOR], ['', '/'], $scriptFile);
            self::assertEquals($files[$i++], $scriptFile);
        }
    }

    public function testGetScriptLabels(): void
    {
        $scriptLabels = $this->scriptManager->getScriptLabels();
        self::assertCount(3, $scriptLabels);

        $labels = [
            'Test1 Bundle Installer',
            'Test2 Bundle Installer',
            'Test Package Installer',
        ];
        $i = 0;
        foreach ($scriptLabels as $scriptLabel) {
            self::assertEquals($labels[$i++], $scriptLabel);
        }
    }

    public function testGetScriptFileByKey(): void
    {
        $scriptFiles = $this->scriptManager->getScriptFiles();
        foreach ($scriptFiles as $scriptKey => $scriptFile) {
            self::assertEquals($scriptFile, $this->scriptManager->getScriptFileByKey($scriptKey));
        }

        self::assertFalse($this->scriptManager->getScriptFileByKey('false_installer'));
    }
}
