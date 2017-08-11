<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Specification;

use Oro\Bundle\TestFrameworkBundle\Behat\Specification\Statistic\FilesystemStatisticRepository;
use Oro\Bundle\TestFrameworkBundle\Behat\Specification\SuiteConfigurationDivider;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SuiteConfiguration;

class SuiteConfigurationDividerTest extends \PHPUnit_Framework_TestCase
{
    protected $featureDuration = [
        '1.feature' => 1,
        '2.feature' => 2,
        '3.feature' => 3,
        '4.feature' => 4,
        '5.feature' => 5,
        '6.feature' => 6,
        '7.feature' => 7,
        '8.feature' => 8,
        '9.feature' => 9,
        '10.feature' => 10,
    ];

    /**
     * @dataProvider divideProvider
     * @param array $suites
     * @param int $maxSeconds
     * @param array $expectedSets
     */
    public function testDivide($suites, $maxSeconds, $expectedSets)
    {
        $divider = new SuiteConfigurationDivider($this->getStatisticRepositoryMock());
        $actualResult = $divider->divide('SetStub', $this->generateConfigs($suites), $maxSeconds);

        $this->assertInternalType('array', $actualResult);
        $this->assertCount(count($expectedSets), $actualResult);

        foreach ($actualResult as $setName => $suiteConfigs) {
            $this->assertArrayHasKey($setName, $expectedSets);
            /** @var SuiteConfiguration $config */
            foreach ($suiteConfigs as $config) {
                $this->assertTrue(
                    in_array($config->getName(), $expectedSets[$setName]),
                    sprintf('Set "%s" not have "%s" suite', $setName, $config->getName())
                );
            }
        }
    }

    public function divideProvider()
    {
        return [
            [
                'suites' => [
                    'SuiteStub_1' => ['1.feature'],
                    'SuiteStub_2' => ['2.feature'],
                    'SuiteStub_3' => ['3.feature'],
                    'SuiteStub_4' => ['4.feature'],
                ],
                'maxSeconds' => 7,
                'sets' => [
                    'SetStub_0' => ['SuiteStub_4', 'SuiteStub_3'], // 7 min
                    'SetStub_1' => ['SuiteStub_2', 'SuiteStub_1'], // 3 min
                ],
            ],
            [
                'suites' => [
                    'SuiteStub_1' => ['1.feature', '2.feature', '3.feature'],
                    'SuiteStub_2' => ['4.feature'],
                    'SuiteStub_3' => ['5.feature'],
                    'SuiteStub_4' => ['6.feature'],
                ],
                'maxSeconds' => 3,
                'sets' => [
                    'SetStub_0' => ['SuiteStub_1'], // 6 min
                    'SetStub_1' => ['SuiteStub_4'], // 6 min
                    'SetStub_2' => ['SuiteStub_3'], // 5 min
                    'SetStub_3' => ['SuiteStub_2'], // 4 min
                ],
            ],
            [
                'suites' => [
                    'SuiteStub_5' => ['5.feature'],
                    'SuiteStub_6' => ['6.feature'],
                    'SuiteStub_7' => ['7.feature'],
                    'SuiteStub_8' => ['8.feature'],
                    'SuiteStub_9' => ['9.feature'],
                    'SuiteStub_10' => ['10.feature'],
                    'SuiteStub_11' => ['11.feature'], // 6 min, average time
                    'SuiteStub_12' => ['12.feature'], // 6 min, average time
                ],
                'maxSeconds' => 15,
                'sets' => [
                    'SetStub_0' => ['SuiteStub_10', 'SuiteStub_5'], // 15 min
                    'SetStub_1' => ['SuiteStub_9', 'SuiteStub_6'], // 15 min
                    'SetStub_2' => ['SuiteStub_8', 'SuiteStub_7'], // 15 min
                    'SetStub_3' => ['SuiteStub_11', 'SuiteStub_12'], // 12 min
                ],
            ],
        ];
    }

    private function generateConfigs(array $configs)
    {
        $result = [];

        foreach ($configs as $suiteName => $features) {
            $config = new SuiteConfiguration($suiteName);
            $config->setSetting('paths', $features);

            $result[$suiteName] = $config;
        }


        return $result;
    }

    private function getStatisticRepositoryMock()
    {
        $repository = new FilesystemStatisticRepository('');
        $reflectionClass = new \ReflectionClass(FilesystemStatisticRepository::class);

        $reflectionProperty = $reflectionClass->getProperty('featureDuration');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($repository, $this->featureDuration);

        $reflectionProperty = $reflectionClass->getProperty('averageFeatureTime');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(
            $repository,
            (int) round(array_sum($this->featureDuration)/count($this->featureDuration))
        );

        return $repository;
    }
}
