<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Suite;

use Behat\Testwork\Specification\SpecificationFinder;
use Oro\Bundle\TestFrameworkBundle\Behat\Specification\SpecificationDivider;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SuiteConfiguration;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SuiteConfigurationRegistry;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Specification\Stub\SpecificationLocatorFilesystemStub;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\KernelStub;

class SuiteConfigurationRegistryTest extends \PHPUnit_Framework_TestCase
{
    private function getSuiteConfigs()
    {
        return [
            'AcmeSuite3' => [
                'type' => null,
                'settings' => [
                    'extra_key' => 'extra_value',
                    'paths' => range(1, 3)
                ],
            ],
            'AcmeSuite5' => [
                'type' => null,
                'settings' => [
                    'paths' => range(1, 5)
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
     * @dataProvider generateSetsProvider
     * @param $divider
     * @param $expectedSets
     */
    public function testGenerateSets($divider, $expectedSets)
    {
        $suiteConfigRegistry = $this->getSuiteConfigRegistry();
        $suiteConfigRegistry->divideSuites(1);
        $suiteConfigRegistry->genererateSets($divider);

        $sets = $suiteConfigRegistry->getSets();
        $this->assertCount(count($expectedSets), $sets);

        foreach ($sets as $setName => $configs) {
            $this->assertArrayHasKey($setName, $expectedSets, sprintf('"%s" is not expected set name', $setName));
            $this->assertSame(
                $expectedSets[$setName],
                array_map(function (SuiteConfiguration $configuration) {
                    return $configuration->getName();
                }, $configs)
            );
        }
    }

    public function generateSetsProvider()
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

        $this->assertCount(count($expectedSuiteConfigs), $suiteConfigRegistry->getSuiteConfigurations());
        foreach ($expectedSuiteConfigs as $name => $count) {
            $this->assertCount($count, $suiteConfigRegistry->getSuiteConfig($name)->getPaths());
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
        $suiteConfigRegistry->genererateSets(8);

        $this->assertInternalType(
            'array',
            $suiteConfigRegistry->getSet(SuiteConfigurationRegistry::PREFIX_SUITE_SET.'_0')
        );
    }

    public function testFilterConfiguration()
    {
        $suiteConfigRegistry = $this->getSuiteConfigRegistry();
        $this->assertArrayHasKey('AcmeSuite0', $suiteConfigRegistry->getSuiteConfigurations());

        $suiteConfigRegistry->filterConfiguration();
        $this->assertArrayNotHasKey('AcmeSuite0', $suiteConfigRegistry->getSuiteConfigurations());
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
            'symfony_bundle',
            $suiteConfigRegistry->getSuiteConfig('AcmeSuite3')->getType()
        );
        $this->assertEquals(
            'extra_value',
            $suiteConfigRegistry->getSuiteConfig('AcmeSuite3')->getSetting('extra_key')
        );
        $this->assertEquals(
            null,
            $suiteConfigRegistry->getSuiteConfig('AcmeSuite5')->getType()
        );
    }

    private function getSuiteConfigRegistry()
    {
        $finder = new SpecificationFinder();
        $finder->registerSpecificationLocator(new SpecificationLocatorFilesystemStub());

        $suiteConfigRegistry = new SuiteConfigurationRegistry(
            new KernelStub([['name' => 'AcmeSuite3']]),
            $finder,
            new SpecificationDivider()
        );

        $suiteConfigRegistry->setSuiteConfigurations($this->getSuiteConfigs());

        return $suiteConfigRegistry;
    }
}
