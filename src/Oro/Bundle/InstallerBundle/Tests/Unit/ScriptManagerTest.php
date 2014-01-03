<?php
namespace Oro\Bundle\InstallerBundleTests\Unit;

use Oro\Bundle\InstallerBundle\ScriptManager;

use Oro\Bundle\InstallerBundle\Tests\Unit\Fixture\src\TestPackage\src\Test1Bundle\TestPackageTest1Bundle;
use Oro\Bundle\InstallerBundle\Tests\Unit\Fixture\src\TestPackage\src\Test2Bundle\TestPackageTest2Bundle;

class ScriptManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScriptManager
     */
    protected $scriptManager;

    protected $kernel;

    public function setUp()
    {
        $this->kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->kernel->expects($this->any())
            ->method('getRootDir')
            ->will($this->returnValue(__DIR__ . '/Fixture/app'));

        $bundles = [
            new TestPackageTest1Bundle(),
            new TestPackageTest2Bundle()
        ];

        $this->kernel->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue($bundles));

        $this->scriptManager = new ScriptManager($this->kernel);
    }

    public function testGetScriptFiles()
    {
        $scriptFiles = $this->scriptManager->getScriptFiles();
        $this->assertEquals(3, count($scriptFiles));

        $files = [
            '/Fixture/src/TestPackage/src/Test1Bundle/install.php',
            '/Fixture/src/TestPackage/src/Test2Bundle/install.php',
            '/Fixture/src/TestPackage/install.php'
        ];
        $i = 0;
        foreach ($scriptFiles as $scriptFile) {
            $scriptFile = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(__DIR__, '', $scriptFile));
            $this->assertEquals($files[$i], $scriptFile);
            $i++;
        }
    }

    public function testGetScriptLabels()
    {
        $scriptLabels = $this->scriptManager->getScriptLabels();
        $this->assertEquals(3, count($scriptLabels));

        $labels = [
            'Test1 Bundle Installer',
            'Test2 Bundle Installer',
            'Test Package Installer'
        ];
        $i = 0;
        foreach ($scriptLabels as $scriptLabel) {
            $this->assertEquals($labels[$i], $scriptLabel);
            $i++;
        }
    }

    public function testGetScriptFileByKey()
    {
        $scriptFiles = $this->scriptManager->getScriptFiles();
        foreach ($scriptFiles as $scriptKey => $scriptFile) {
            $this->assertEquals($scriptFile, $this->scriptManager->getScriptFileByKey($scriptKey));
        }

        $this->assertFalse($this->scriptManager->getScriptFileByKey('false_installer'));
    }
}
