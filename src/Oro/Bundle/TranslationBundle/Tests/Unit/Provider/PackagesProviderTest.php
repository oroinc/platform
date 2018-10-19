<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Composer\Package\Package;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Bundle\TranslationBundle\Provider\PackageProviderInterface;
use Oro\Bundle\TranslationBundle\Provider\PackagesProvider;

class PackagesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PackageManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $pm;

    protected function setUp()
    {
        $this->pm = $this->getMockBuilder('Oro\Bundle\DistributionBundle\Manager\PackageManager')
            ->disableOriginalConstructor()->getMock();
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
     * @param array $providers
     * @param array $expectedResult
     */
    public function testGetInstalledPackages(
        array $packages = [],
        array $bundles = [],
        array $providers = [],
        array $expectedResult = []
    ) {
        $provider = new PackagesProvider($this->pm, $bundles, 'rootDir', 'rootDir/cache/composer', $providers);
        $this->pm->expects($this->once())->method('getInstalled')
            ->will($this->returnValue($packages));

        $this->assertEquals($expectedResult, $provider->getInstalledPackages());
    }

    /**
     * @return array
     */
    public function installedPackagesProvider()
    {
        $provider1 = $this->getPackagesProvider();
        $provider2 = $this->getPackagesProvider(['package1', 'package2']);
        $provider3 = $this->getPackagesProvider(['package3', 'package1']);

        return [
            'empty result by default' => [],
            'contains packages info' => [[new Package('testName', '1', '1')], [], [], ['testname']],
            'contains bundles first namespace part' => [
                [],
                ['Oro\Bundle\TranslationBundle\OroTranslationBundle'],
                [],
                ['Oro']
            ],
            'packages from other providers' => [
                [],
                [],
                [$provider1, $provider2, $provider3],
                ['package1', 'package2', 'package3']
            ],
            'packages merged from both source' => [
                [new Package('testName', '1', '1')],
                ['Oro\Bundle\TranslationBundle\OroTranslationBundle'],
                [$provider1, $provider2, $provider3],
                ['testname', 'Oro', 'package1', 'package2', 'package3']
            ]
        ];
    }

    /**
     * @param array|string[] $names
     * @return PackageProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPackagesProvider(array $names = [])
    {
        $otherProvider = $this->createMock(PackageProviderInterface::class);
        $otherProvider->expects($this->any())->method('getInstalledPackages')
            ->willReturn($names);

        return $otherProvider;
    }
}
