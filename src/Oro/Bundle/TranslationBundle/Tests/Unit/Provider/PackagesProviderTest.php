<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Composer\Package\Package;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Bundle\TranslationBundle\Provider\PackagesProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackagesProviderExtensionInterface;

class PackagesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $pm;

    /** @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject */
    protected $pml;

    protected function setUp()
    {
        $this->pm = $this->getMockBuilder('Oro\Bundle\DistributionBundle\Manager\PackageManager')
            ->disableOriginalConstructor()->getMock();
        $this->pml = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()->getMock();

        $this->pml->expects($this->any())->method('getService')
            ->will($this->returnValue($this->pm));
    }

    protected function tearDown()
    {
        unset($this->pm);
    }

    /**
     * @dataProvider installedPackagesProvider
     *
     * @param array $packages
     * @param array $bundles
     * @param array $extensions
     * @param array $expectedResult
     */
    public function testGetInstalledPackages(
        array $packages = [],
        array $bundles = [],
        array $extensions = [],
        array $expectedResult = []
    ) {
        $provider = new PackagesProvider($this->pml, $bundles, 'rootDir', 'rootDir/cache/composer');
        $this->pm->expects($this->once())->method('getInstalled')
            ->will($this->returnValue($packages));

        foreach ($extensions as $extension) {
            $provider->addExtension($extension);
        }

        $this->assertEquals($expectedResult, $provider->getInstalledPackages());
    }

    /**
     * @return array
     */
    public function installedPackagesProvider()
    {
        $extension1 = $this->getProviderExtension();
        $extension2 = $this->getProviderExtension(['package1', 'package2']);
        $extension3 = $this->getProviderExtension(['package3', 'package1']);

        return [
            'empty result by default' => [],
            'contains packages info' => [[new Package('testName', '1', '1')], [], [], ['testname']],
            'contains bundles first namespace part' => [
                [],
                ['Oro\Bundle\TranslationBundle\OroTranslationBundle'],
                [],
                ['Oro']
            ],
            'packages from provider extensions' => [
                [],
                [],
                [$extension1, $extension2, $extension3],
                ['package1', 'package2', 'package3']
            ],
            'packages merged from both source' => [
                [new Package('testName', '1', '1')],
                ['Oro\Bundle\TranslationBundle\OroTranslationBundle'],
                [$extension1, $extension2, $extension3],
                ['testname', 'Oro', 'package1', 'package2', 'package3']
            ]
        ];
    }

    /**
     * @param array|string[] $names
     * @return TranslationPackagesProviderExtensionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProviderExtension(array $names = [])
    {
        $extension = $this->createMock(TranslationPackagesProviderExtensionInterface::class);
        $extension->expects($this->any())->method('getPackageNames')->willReturn($names);

        return $extension;
    }
}
