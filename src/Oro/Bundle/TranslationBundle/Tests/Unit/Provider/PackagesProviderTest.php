<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Composer\Package\Package;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Bundle\TranslationBundle\Provider\PackagesProvider;

class PackagesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $pm;

    public function setUp()
    {
        $this->pm = $this->getMockBuilder('Oro\Bundle\DistributionBundle\Manager\PackageManager')
            ->disableOriginalConstructor()->getMock();
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
        $provider = new PackagesProvider($this->pm, $bundles);
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
