<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Suite;

use Behat\Testwork\Specification\SpecificationFinder;
use Behat\Testwork\Suite\Generator\GenericSuiteGenerator;
use Behat\Testwork\Suite\Suite;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider\FeatureAvgTimeRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\FeatureStatisticRepository;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\FeaturePathLocator;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\SpecificationCountDivider;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\SuiteConfigurationDivider;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Specification\Stub\SpecificationLocatorFilesystemStub;

class SuiteConfigurationRegistryTest extends \PHPUnit\Framework\TestCase
{
    private function getSuiteConfigs()
    {
        return [
            'AcmeSuite3' => [
                'type' => null,
                'settings' => [
                    'extra_key' => 'extra_value',
                    'paths' => array_map(function ($n) {
                        return '/tmp/'.$n;
                    }, range(1, 3))
                ],
            ],
            'AcmeSuite5' => [
                'type' => null,
                'settings' => [
                    'paths' => array_map(function ($n) {
                        return '/tmp/'.$n;
                    }, range(1, 5))
                ],
            ],
            'AcmeSuite0' => [
                'type' => null,
                'settings' => [
                    'paths' => [],
                ],
            ]
        ];
    }

    /**
     * @dataProvider generateSetsDividedByCountProvider
     * @param $divider
     * @param $expectedSets
     */
    public function testGenerateSetsDividedByCount($divider, $expectedSets)
    {
        $suiteConfigRegistry = $this->getSuiteConfigRegistry();
        $suiteConfigRegistry->divideSuites(1);
        $suiteConfigRegistry->generateSetsDividedByCount($divider);

        $sets = $suiteConfigRegistry->getSets();
        $this->assertCount(count($expectedSets), $sets);

        foreach ($sets as $setName => $configs) {
            $this->assertArrayHasKey($setName, $expectedSets, sprintf('"%s" is not expected set name', $setName));
            $this->assertSame(
                $expectedSets[$setName],
                array_map(function (Suite $configuration) {
                    return $configuration->getName();
                }, $configs)
            );
        }
    }

    public function generateSetsDividedByCountProvider()
    {
        return [
            [
                'divider' => 10,
                'expectedSets' => [
                    SuiteConfigurationRegistry::PREFIX_SUITE_SET.'_0' => [
                        'AcmeSuite3_0',
                        'AcmeSuite3_1',
                        'AcmeSuite3_2',
                        'AcmeSuite5_0',
                        'AcmeSuite5_1',
                        'AcmeSuite5_2',
                        'AcmeSuite5_3',
                        'AcmeSuite5_4',
                    ],
                ],
            ],
            [
                'divider' => 5,
                'expectedSets' => [
                    SuiteConfigurationRegistry::PREFIX_SUITE_SET.'_0' => [
                        'AcmeSuite5_2',
                        'AcmeSuite5_3',
                        'AcmeSuite5_4',
                        'AcmeSuite3_0',
                    ],
                    SuiteConfigurationRegistry::PREFIX_SUITE_SET.'_1' => [
                        'AcmeSuite3_1',
                        'AcmeSuite3_2',
                        'AcmeSuite5_0',
                        'AcmeSuite5_1',
                    ],
                ],
            ],
            [
                'divider' => 3,
                'expectedSets' => [
                    SuiteConfigurationRegistry::PREFIX_SUITE_SET.'_0' => [
                        'AcmeSuite3_0',
                        'AcmeSuite3_1',
                        'AcmeSuite3_2',
                    ],
                    SuiteConfigurationRegistry::PREFIX_SUITE_SET.'_1' => [
                        'AcmeSuite5_3',
                        'AcmeSuite5_4',
                        'AcmeSuite5_0',
                    ],
                    SuiteConfigurationRegistry::PREFIX_SUITE_SET.'_2' => [
                        'AcmeSuite5_1',
                        'AcmeSuite5_2',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dividingSuitesProvider
     * @param int $divider
     * @param array $suiteConfigs
     * @param array $expectedSuites
     */
    public function testDivideSuites($divider, $expectedSuiteConfigs)
    {
        $suiteConfigRegistry = $this->getSuiteConfigRegistry();
        $suiteConfigRegistry->divideSuites($divider);

        $this->assertCount(count($expectedSuiteConfigs), $suiteConfigRegistry->getSuites());
        foreach ($expectedSuiteConfigs as $name => $count) {
            $this->assertCount($count, $suiteConfigRegistry->getSuiteConfig($name)->getSetting('paths'));
        }
    }

    public function dividingSuitesProvider()
    {
        return [
            [
                'divider' => 4,
                'expectedSuites' => [
                    'AcmeSuite3_0' => 3,
                    'AcmeSuite5_0' => 3,
                    'AcmeSuite5_1' => 2,
                ],
            ],
            [
                'divider' => 10,
                'expectedSuites' => [
                    'AcmeSuite3_0' => 3,
                    'AcmeSuite5_0' => 5,
                ],
            ],
            [
                'divider' => 1,
                'expectedSuites' => [
                    'AcmeSuite3_0' => 1,
                    'AcmeSuite3_1' => 1,
                    'AcmeSuite3_2' => 1,
                    'AcmeSuite5_0' => 1,
                    'AcmeSuite5_1' => 1,
                    'AcmeSuite5_2' => 1,
                    'AcmeSuite5_3' => 1,
                    'AcmeSuite5_4' => 1,
                ],
            ],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Suite with 'Acme0' name does not configured
     * @expectedExceptionMessage Configured suites: 'AcmeSuite3, AcmeSuite5'
     */
    public function testNotExistentSuiteConfigException()
    {
        $suiteConfigRegistry = $this->getSuiteConfigRegistry();
        $suiteConfigRegistry->getSuiteConfig('Acme0');
    }

    public function testGetSet()
    {
        $suiteConfigRegistry = $this->getSuiteConfigRegistry();
        $suiteConfigRegistry->generateSetsDividedByCount(8);

        $this->assertInternalType(
            'array',
            $suiteConfigRegistry->getSet(SuiteConfigurationRegistry::PREFIX_SUITE_SET.'_0')
        );
    }

    public function testFilterConfiguration()
    {
        $suiteConfigRegistry = $this->getSuiteConfigRegistry();
        $this->assertArrayNotHasKey('AcmeSuite0', $suiteConfigRegistry->getSuites());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Suite set with "Non existent" name does not registered
     */
    public function testNotExistentSuiteSetException()
    {
        $suiteConfigRegistry = $this->getSuiteConfigRegistry();
        $suiteConfigRegistry->getSet('Non existent');
    }

    public function testSuiteConfigCreation()
    {
        $suiteConfigRegistry = $this->getSuiteConfigRegistry();

        $this->assertEquals(
            'extra_value',
            $suiteConfigRegistry->getSuiteConfig('AcmeSuite3')->getSetting('extra_key')
        );
        $this->assertEquals(
            false,
            $suiteConfigRegistry->getSuiteConfig('AcmeSuite5')->hasSetting('type')
        );
    }

    private function getSuiteConfigRegistry()
    {
        $finder = new SpecificationFinder();
        $finder->registerSpecificationLocator(new SpecificationLocatorFilesystemStub());

        $suiteConfigRegistry = new SuiteConfigurationRegistry(
            $finder,
            new SpecificationCountDivider(),
            new SuiteConfigurationDivider(
                $this->getFeatureAvgTimeRegistryMock(),
                new FeaturePathLocator('')
            ),
            $this->getFeaturePathLocatorMock()
        );
        $suiteConfigRegistry->addSuiteGenerator(new GenericSuiteGenerator());

        $suiteConfigRegistry->setSuiteConfigurations($this->getSuiteConfigs());

        return $suiteConfigRegistry;
    }

    /**
     * @return FeatureAvgTimeRegistry
     */
    private function getFeatureAvgTimeRegistryMock()
    {
        return new FeatureAvgTimeRegistry();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FeaturePathLocator
     */
    private function getFeaturePathLocatorMock()
    {
        $featurePathLocator = $this->getMockBuilder(FeaturePathLocator::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRelativePath'])
            ->getMock()
        ;
        $featurePathLocator->method('getRelativePath')->willReturnArgument(0);

        return $featurePathLocator;
    }
}
