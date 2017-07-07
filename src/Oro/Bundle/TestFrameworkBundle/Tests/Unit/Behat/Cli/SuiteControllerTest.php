<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli;

use Behat\Symfony2Extension\Suite\SymfonySuiteGenerator;
use Behat\Testwork\Specification\SpecificationFinder;
use Behat\Testwork\Suite\Generator\GenericSuiteGenerator;
use Behat\Testwork\Suite\SuiteRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Cli\SuiteController;
use Oro\Bundle\TestFrameworkBundle\Behat\Specification\SpecificationDivider;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Specification\Stub\SpecificationLocatorFilesystemStub;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\TestBundle;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\HttpKernel\KernelInterface;

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

    /**
     * @dataProvider testDividingProvider
     *
     * @param int $divider
     * @param array $suiteConfigs
     * @param array $expectedSuites
     */
    public function testDividing($divider, array $suiteConfigs, array $expectedSuites)
    {
        $kernel = $this->getMockBuilder(KernelInterface::class)->getMock();
        $kernel->method('getBundle')->willReturn(new TestBundle('TestBundle'));

        $suiteRegistry = new SuiteRegistry();
        $suiteRegistry->registerSuiteGenerator(new GenericSuiteGenerator());
        $suiteRegistry->registerSuiteGenerator(new SymfonySuiteGenerator($kernel));

        $specFinder = new SpecificationFinder();
        $specFinder->registerSpecificationLocator(
            new SpecificationLocatorFilesystemStub(array_merge(array_map(function ($config) {
                return $config['settings']['paths'];
            }, $suiteConfigs)))
        );

        $controller = new SuiteController(
            $suiteRegistry,
            $suiteConfigs,
            new SpecificationDivider($specFinder),
            $kernel
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
                    'AcmeSuite#2' => 4,
                    'AcmeSuite#3' => 4,
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
                    'AcmeSuite#2' => 4,
                    'AcmeSuite#3' => 4,
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
