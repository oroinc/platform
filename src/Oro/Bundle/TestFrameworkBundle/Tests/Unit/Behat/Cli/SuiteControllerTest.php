<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli;

use Behat\Testwork\Suite\Generator\GenericSuiteGenerator;
use Behat\Testwork\Suite\Suite;
use Behat\Testwork\Suite\SuiteRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Cli\SuiteController;
use Oro\Bundle\TestFrameworkBundle\Behat\Specification\SpecificationDivider;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Filesystem\Filesystem;

class SuiteControllerTest extends \PHPUnit_Framework_TestCase
{
    private static $featuresDir = '/test_behat_suite_features';
    private static $featuresDir10 = '/test_behat_suite_features/10';
    private static $featuresDir5 = '/test_behat_suite_features/5';
    private static $featuresDir3 = '/test_behat_suite_features/3';

    /**
     * @beforeClass
     */
    public static function initialClassSetup()
    {
        mkdir(sys_get_temp_dir().self::$featuresDir);

        self::createFeatures(10, sys_get_temp_dir().self::$featuresDir10);
        self::createFeatures(5, sys_get_temp_dir().self::$featuresDir5);
        self::createFeatures(3, sys_get_temp_dir().self::$featuresDir3);
    }

    private static function createFeatures($count, $dir)
    {
        mkdir($dir);
        for ($i = 1; $i <= $count; $i++) {
            touch($dir.'/'.$i.'.feature');
        }
    }

    /**
     * @afterClass
     */
    public static function finalClassTeardown()
    {
        $dir = sys_get_temp_dir().self::$featuresDir;
        self::delTree($dir);
    }

    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), ['.','..']);

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }
//    /**
//     * @expectedException \Behat\Testwork\Suite\Exception\SuiteNotFoundException
//     * @expectedExceptionMessage `Unregistered` suite is not found or has not been properly registered.
//     */
//    public function testSuiteNotFountException()
//    {
//        $suiteRegistry = new SuiteRegistry();
//        $suiteRegistry->registerSuiteGenerator(new GenericSuiteGenerator());
//        $controller = new SuiteController(
//            $suiteRegistry,
//            $this->createFakeConfigurations($this->suites),
//            ['One', 'Two', 'Unregistered']
//        );
//
//        $input = new InputStub('', [], ['applicable-suites' => true]);
//        $controller->execute($input, new OutputStub());
//    }

    /**
     * @dataProvider testDividingProvider
     *
     * @param int $divider
     * @param array $suiteConfigs
     * @param array $expectedSuites
     */
    public function testDividing($divider, array $suiteConfigs, array $expectedSuites)
    {
        $suiteRegistry = new SuiteRegistry();
        $suiteRegistry->registerSuiteGenerator(new GenericSuiteGenerator());
        $controller = new SuiteController(
            $suiteRegistry,
            $suiteConfigs,
            new SpecificationDivider()
        );

        $input = new InputStub('', [], ['suite-divider' => $divider]);
        $controller->execute($input, new OutputStub());

        $actualSuites = [];

        foreach ($suiteRegistry->getSuites() as $suite) {
            $actualSuites[$suite->getName()] = count($suite->getSetting('paths'));
        }

        $this->assertSame($expectedSuites, $actualSuites);
    }

    public function testDividingProvider()
    {
        return [
            '18/5 in nested directories' => [
                'divider' => 5,
                'suiteConfigs' => [
                    'AcmeSuite' => [
                        'type' => null,
                        'settings' => [
                            'paths' => [sys_get_temp_dir().self::$featuresDir]
                        ],
                    ],
                ],
                'expectedSuites' => [
                    'AcmeSuite#0' => 5,
                    'AcmeSuite#1' => 5,
                    'AcmeSuite#2' => 5,
                    'AcmeSuite#3' => 3,
                ],
            ],
            '18/5 with duplicated features' => [
                'divider' => 5,
                'suiteConfigs' => [
                    'AcmeSuite' => [
                        'type' => null,
                        'settings' => [
                            'paths' => [
                                sys_get_temp_dir().self::$featuresDir,
                                sys_get_temp_dir().self::$featuresDir3,
                                sys_get_temp_dir().self::$featuresDir5,
                                sys_get_temp_dir().self::$featuresDir10,
                            ]
                        ],
                    ],
                ],
                'expectedSuites' => [
                    'AcmeSuite#0' => 5,
                    'AcmeSuite#1' => 5,
                    'AcmeSuite#2' => 5,
                    'AcmeSuite#3' => 3,
                ],
            ],
            '3/1' => [
                'divider' => 1,
                'suiteConfigs' => [
                    'AcmeSuite' => [
                        'type' => null,
                        'settings' => [
                            'paths' => [sys_get_temp_dir().self::$featuresDir3]
                        ],
                    ],
                ],
                'expectedSuites' => [
                    'AcmeSuite#0' => 1,
                    'AcmeSuite#1' => 1,
                    'AcmeSuite#2' => 1,
                ],
            ],
            '8/3 in two directories' => [
                'divider' => 3,
                'suiteConfigs' => [
                    'AcmeSuite' => [
                        'type' => null,
                        'settings' => [
                            'paths' => [
                                sys_get_temp_dir().self::$featuresDir3,
                                sys_get_temp_dir().self::$featuresDir5,
                            ],
                        ],
                    ],
                ],
                'expectedSuites' => [
                    'AcmeSuite#0' => 3,
                    'AcmeSuite#1' => 3,
                    'AcmeSuite#2' => 2,
                ],
            ],
        ];
    }

    /**
     * @param array $availableSuites in format ['SuiteNameOne', 'SuiteNameTwo']
     * @return array in format ['SuiteNameOne' => ['type' => null, 'settings' => []]]
     */
    private function createFakeConfigurations(array $availableSuites)
    {
        return array_map(function () {
            return [
                'type' => null,
                'settings' => [],
            ];
        }, array_flip($availableSuites));
    }
}
