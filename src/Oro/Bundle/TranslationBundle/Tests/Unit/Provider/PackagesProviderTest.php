<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Composer\Package\Package;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Bundle\TranslationBundle\Provider\PackageProviderInterface;
use Oro\Bundle\TranslationBundle\Provider\PackagesProvider;

class PackagesProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider installedPackagesProvider
     *
     * @param array $packages
     * @param array $bundles
     * @param array $providers
     * @param array $expectedResult
     */
    public function testGetInstalledPackages(
        array $bundles = [],
        array $providers = [],
        array $expectedResult = []
    ) {
        $provider = new PackagesProvider($bundles, 'rootDir', $providers);

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
            'contains bundles first namespace part' => [
                ['Oro\Bundle\TranslationBundle\OroTranslationBundle'],
                [],
                ['Oro']
            ],
            'packages from other providers' => [
                [],
                [$provider1, $provider2, $provider3],
                ['package1', 'package2', 'package3']
            ],
            'packages from providers and bundles' => [
                ['Oro\Bundle\TranslationBundle\OroTranslationBundle'],
                [$provider1, $provider2, $provider3],
                ['Oro', 'package1', 'package2', 'package3']
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
