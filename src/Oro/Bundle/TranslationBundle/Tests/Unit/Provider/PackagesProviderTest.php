<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Composer\Package\Package;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Bundle\TranslationBundle\Provider\PackagesProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class PackagesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $pm;

    /** @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject */
    protected $pml;

    public function setUp()
    {
        $this->pm  = $this->getMockBuilder('Oro\Bundle\DistributionBundle\Manager\PackageManager')
            ->disableOriginalConstructor()->getMock();
        $this->pml = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()->getMock();

        $this->pml->expects($this->any())->method('getService')
            ->will($this->returnValue($this->pm));
    }

    public function tearDown()
    {
        unset($this->pm);
    }

    /**
     * @dataProvider installedPackagesProvider
     *
     * @param array $packages
     * @param array $bundles
     * @param array $expectedResult
     */
    public function testGetInstalledPackages($packages = [], $bundles = [], $expectedResult = [])
    {
        $provider = new PackagesProvider($this->pml, $bundles, 'rootDir');
        $this->pm->expects($this->once())->method('getInstalled')
            ->will($this->returnValue($packages));

        $this->assertEquals($expectedResult, $provider->getInstalledPackages());
    }

    /**
     * @return array
     */
    public function installedPackagesProvider()
    {
        return [
            'empty result by default'               => [],
            'contains packages info'                => [[new Package('testName', '1', '1')], [], ['testname']],
            'contains bundles first namespace part' => [
                [],
                ['Oro\Bundle\TranslationBundle\OroTranslationBundle'],
                ['Oro']
            ],
            'packages merged from both source'      => [
                [new Package('testName', '1', '1')],
                ['Oro\Bundle\TranslationBundle\OroTranslationBundle'],
                ['testname', 'Oro']
            ]
        ];
    }
}
