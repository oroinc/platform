<?php
namespace Oro\Bundle\InstallerBundleTests\Unit;

use Oro\Bundle\InstallerBundle\InstallerProvider;

use Oro\Bundle\InstallerBundle\Tests\Unit\Fixture\src\TestPackage\src\Test1Bundle\TestPackageTest1Bundle;
use Oro\Bundle\InstallerBundle\Tests\Unit\Fixture\src\TestPackage\src\Test2Bundle\TestPackageTest2Bundle;

class InstallerProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InstallerProvider
     */
    protected $installerProvider;

    protected $kernel;

    protected $installers = [];

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

        $this->installerProvider = new InstallerProvider($this->kernel);

        $this->installers = $this->installerProvider->getInstallerScriptList();
    }

    public function testGetInstallerScriptList()
    {
        $this->assertEquals(3, count($this->installers));
        $i = 0;
        $labels = [
            'Test1 Bundle Installer',
            'Test2 Bundle Installer',
            'Test Package Installer'
        ];
        $indexes = [0, 2, 3];
        foreach ($this->installers as $installer) {
            $this->assertEquals($labels[$i], $installer['label']);
            $this->assertEquals($indexes[$i], $installer['index']);
            $i++;
        }
    }

    public function testGetInstallerScriptsLabels()
    {
        $labels = $this->installerProvider->getInstallerScriptsLabels();
        foreach ($this->installers as $installer) {
            $this->assertEquals($installer['label'], $labels[$installer['key']]);
        }
    }

    public function testGetInstallerScriptByKey()
    {
        foreach ($this->installers as $installer) {
            $this->assertEquals($installer, $this->installerProvider->getInstallerScriptByKey($installer['key']));
        }
        $this->assertFalse($this->installerProvider->getInstallerScriptByKey('false_installer'));
    }

    public function testRunInstallerScript()
    {
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\StreamOutput')
            ->disableOriginalConstructor()
            ->getMock();
        $testInstaller = array_keys($this->installers)[0];
        $output->expects($this->at(0))
            ->method('writeln')
            ->with('');
        $output->expects($this->at(1))
            ->method('writeln');
        $output->expects($this->at(2))
            ->method('writeln')
            ->with('Test1 Bundle Installer data');
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $this->installerProvider->runInstallerScript($testInstaller, $output, $container);
    }

    public function testRunWromgInstaller()
    {
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\StreamOutput')
            ->disableOriginalConstructor()
            ->getMock();
        $output->expects($this->once())
            ->method('writeln')
            ->with('Installer "wrong_installer" was not found');
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $this->installerProvider->runInstallerScript('wrong_installer', $output, $container);
    }
}
