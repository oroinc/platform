<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RequireJSBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataProviderExceptionConfigTree
     */
    public function testExceptionConfigTree($options, $exception)
    {
        $this->expectException($exception);

        $processor = new Processor();
        $configuration = new Configuration(array());
        $processor->processConfiguration($configuration, array($options));
    }

    public function dataProviderExceptionConfigTree()
    {
        return array(
            array(
                array(
                    'config' => array('waitSeconds' => -3),
                ),
                '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException'
            ),
            array(
                array(
                    'config' => array('scriptType' => ''),
                ),
                '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException'
            ),
            array(
                array(
                    'building_timeout' => -3,
                ),
                '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException'
            ),
            array(
                array(
                    'build' => array('optimize' => 'test'),
                ),
                '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException'
            ),
        );
    }

    /**
     * @dataProvider dataProviderConfigTree
     */
    public function testConfigTree($options, $expects)
    {
        $processor = new Processor();
        $configuration = new Configuration(array());
        $result = $processor->processConfiguration($configuration, array($options));

        $this->assertEquals($expects, $result);
    }

    public function dataProviderConfigTree()
    {
        return [
            [
                [],
                [
                    'config' => [
                        'waitSeconds' => 0,
                    ],
                    'web_root' => '%kernel.project_dir%/public/',
                    'build_path' => 'js/app.min.js',
                    'building_timeout' => 60,
                    'build_logger' => false,
                    'build' => [
                        'optimize' => 'uglify2',
                        'paths' => [],
                    ]
                ]
            ],
            [
                [
                    'config' => [
                        'waitSeconds' => 0,
                        'enforceDefine' => true,
                        'scriptType' => 'text/javascript'
                    ],
                    'build_path' => 'js/test/app.min.js',
                    'building_timeout' => 3600,
                    'build_logger' => false,
                    'build' => [
                        'optimize' => 'none',
                        'generateSourceMaps' => false,
                        'preserveLicenseComments' => true,
                        'useSourceUrl' => false,
                        'paths' => [],
                    ]
                ],
                [
                    'config' => [
                        'waitSeconds' => 0,
                        'enforceDefine' => true,
                        'scriptType' => 'text/javascript',
                    ],
                    'web_root' => '%kernel.project_dir%/public/',
                    'build_path' => 'js/test/app.min.js',
                    'building_timeout' => 3600,
                    'build_logger' => false,
                    'build' => [
                        'optimize' => 'none',
                        'generateSourceMaps' => false,
                        'preserveLicenseComments' => 1,
                        'useSourceUrl' => false,
                        'paths' => [],
                    ],
                ]
            ],
        ];
    }
}
