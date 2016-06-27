<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\RequireJSBundle\Provider\Config as RequireJSConfigProvider;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequireJSConfigProvider
     */
    protected $configProvider;

    protected function setUp()
    {
        $parameters = [
            'oro_require_js' => [
                'build_path' => 'js/app.min.js',

            ],
            'oro_require_js.web_root' => '.',
            'kernel.bundles' => [
                'Oro\Bundle\RequireJSBundle\Tests\Unit\Fixtures\TestBundle\TestBundle',
                'Oro\Bundle\RequireJSBundle\Tests\Unit\Fixtures\SecondTestBundle\SecondTestBundle',
            ]
        ];

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())
            ->method('getParameter')
            ->will($this->returnCallback(
                function ($name) use (&$parameters) {
                    return $parameters[$name];
                }
            ));

        $templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $templating->expects($this->any())
            ->method('render')
            ->will($this->returnArgument(1));

        $template = '';

        $this->configProvider = new RequireJSConfigProvider($container, $templating, $template);
    }

    public function testGetMainConfig()
    {
        $expected = [
            'config' => [
                'paths' => [
                    'oro/test' => 'orosecondtest/js/second-test'
                ],
                'config_key' => '_main'
            ]
        ];
        $this->assertEquals($expected, $this->configProvider->getMainConfig());

        $expected['config']['paths']['oro/test2'] = 'orotest/js/test2';

        /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->getMock('\Doctrine\Common\Cache\PhpFileCache', [], [], '', false);
        $cache->expects($this->any())
            ->method('fetch')
            ->will($this->returnValue([
                '_main' => [
                    'mainConfig' => $expected
                ]
            ]));
        $this->configProvider->setCache($cache);

        $this->assertEquals($expected, $this->configProvider->getMainConfig());
    }

    public function testGenerateMainConfig()
    {
        $this->assertEquals(
            [
                'config' => [
                    'paths' => [
                        'oro/test' => 'orosecondtest/js/second-test'
                    ],
                    'config_key' => '_main'
                ]
            ],
            $this->configProvider->generateMainConfig()
        );
    }

    public function testGenerateBuildConfig()
    {
        $this->assertEquals(
            [
                'paths' => [
                    'oro/test' => 'empty:',
                    'require-config' => './js/require-config',
                    'require-lib' => 'ororequirejs/lib/require',
                ],
                'baseUrl' => './bundles',
                'out' => './js/app.min.js',
                'mainConfigFile' => './js/require-config.js',
                'include' => ['require-config', 'require-lib', 'oro/test']
            ],
            $this->configProvider->generateBuildConfig('main-config.js')
        );
    }

    public function testCollectConfigs()
    {
        $this->assertEquals(
            [
                'build_path' => 'js/app.min.js',
                'config' => [
                    'paths' => [
                        'oro/test' => 'bundles/orosecondtest/js/second-test.js'
                    ],
                    'config_key' => '_main'
                ],
                'build' => [
                    'paths' => [
                        'oro/test' => 'empty:'
                    ]
                ]
            ],
            $this->configProvider->collectConfigs()
        );
    }
}
