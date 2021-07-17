<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\HelpBundle\Annotation\Help;
use Oro\Bundle\HelpBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\HelpBundle\Provider\HelpLinkProvider;
use Oro\Bundle\HelpBundle\Tests\Unit\Fixtures\Bundles\TestBundle\Controller\TestController;
use Oro\Bundle\HelpBundle\Tests\Unit\Fixtures\Bundles\TestBundle\OroTestBundle as TestBundle;
use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

class HelpLinkProviderTest extends \PHPUnit\Framework\TestCase
{
    private const VERSION = '1.0';

    private const WIKI      = 'http://wiki.test.com';
    private const TEST      = 'http://test.com';
    private const TEST_WIKI = self::TEST . '/wiki';

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var ControllerClassProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $controllerClassProvider;

    /** @var VersionHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->controllerClassProvider = $this->createMock(ControllerClassProvider::class);
        $this->helper = $this->createMock(VersionHelper::class);
        $this->cache = $this->createMock(CacheProvider::class);
    }

    private function getHelpLinkProvider(array $defaultConfig = [], array $config = []): HelpLinkProvider
    {
        $configProvider = $this->createMock(ConfigurationProvider::class);
        $configProvider->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($config);

        $bundle = new TestBundle();
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects($this->any())
            ->method('getBundle')
            ->with($bundle->getName(), $this->isFalse())
            ->willReturn($bundle);
        $kernel->expects($this->any())
            ->method('getBundles')
            ->willReturn([$bundle->getName() => $bundle]);

        return new HelpLinkProvider(
            $defaultConfig,
            $configProvider,
            $this->requestStack,
            $this->controllerClassProvider,
            new ControllerNameParser($kernel),
            $this->helper,
            $this->cache
        );
    }

    public function testGetHelpLinkCached()
    {
        $expectedLink = self::TEST . '/help/test?v=1.1';
        $routeName = 'test_route';

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($routeName)
            ->will($this->returnValue($expectedLink));
        $this->cache->expects($this->never())
            ->method('save');

        $request = new Request();
        $request->attributes->add(['_route' => $routeName]);
        $this->requestStack->expects($this->any())
            ->method('getMasterRequest')
            ->willReturn($request);

        $helpLinkProvider = $this->getHelpLinkProvider();
        $this->assertEquals($expectedLink, $helpLinkProvider->getHelpLinkUrl());
    }

    public function testGetHelpLinkWithoutRouteAndWithoutCache()
    {
        $expectedLink = 'http://example.com/';

        $this->cache->expects($this->never())
            ->method('fetch');
        $this->cache->expects($this->never())
            ->method('save');

        $helpLinkProvider = $this->getHelpLinkProvider(
            ['link' => 'http://example.com/']
        );
        $this->assertEquals($expectedLink, $helpLinkProvider->getHelpLinkUrl());
    }

    public function testGetHelpLinkWithoutRouteAndWithCache()
    {
        $expectedLink = 'http://example.com/';

        $this->cache->expects($this->never())
            ->method('fetch');
        $this->cache->expects($this->never())
            ->method('save');

        $helpLinkProvider = $this->getHelpLinkProvider(
            ['link' => 'http://example.com/']
        );
        $this->assertEquals($expectedLink, $helpLinkProvider->getHelpLinkUrl());
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testGetHelpLinkUrl(
        array $configuration,
        array $controllers,
        array $requestAttributes,
        string $expectedLink
    ) {
        $this->helper
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue(self::VERSION));

        $request = new Request();
        $request->attributes->add($requestAttributes);
        $this->requestStack->expects($this->any())
            ->method('getMasterRequest')
            ->willReturn($request);
        $this->controllerClassProvider->expects($this->any())
            ->method('getControllers')
            ->willReturn($controllers);

        if (isset($requestAttributes['_route'])) {
            $this->cache->expects($this->once())
                ->method('fetch')
                ->with($requestAttributes['_route'])
                ->willReturn(false);
            $this->cache->expects($this->once())
                ->method('save')
                ->with($requestAttributes['_route'], $expectedLink);
        }

        $defaultConfig = [];
        if (array_key_exists('defaults', $configuration)) {
            $defaultConfig = $configuration['defaults'];
            unset($configuration['defaults']);
        }
        $helpLinkProvider = $this->getHelpLinkProvider($defaultConfig, $configuration);
        $this->assertEquals($expectedLink, $helpLinkProvider->getHelpLinkUrl());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function configurationDataProvider()
    {
        return [
            'simple default no cache'                   => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/'
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::TEST_WIKI . '/Oro/OroTestBundle/Test_run?v=' . self::VERSION
            ],
            'simple default with cache'                 => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/'
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::TEST_WIKI . '/Oro/OroTestBundle/Test_run?v=' . self::VERSION
            ],
            'default with prefix'                       => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::TEST_WIKI . '/Third_Party/Oro/OroTestBundle/Test_run?v=' . self::VERSION
            ],
            'default with link'                         => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party',
                        'link'   => self::WIKI . '/'
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::WIKI . '/'
            ],
            'vendor link'                               => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'vendors'  => [
                        'Oro' => [
                            'link' => self::WIKI . '/'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::WIKI . '/'
            ],
            'vendor config'                             => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'vendors'  => [
                        'Oro' => [
                            'alias'  => 'CustomVendor',
                            'prefix' => 'Prefix',
                            'server' => self::WIKI . '/'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::WIKI . '/Prefix/CustomVendor/OroTestBundle/Test_run?v=' . self::VERSION
            ],
            'vendor uri'                                => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'vendors'  => [
                        'Oro' => [
                            'uri' => 'test'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::TEST_WIKI . '/test?v=' . self::VERSION
            ],
            'bundle config'                             => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        'OroTestBundle' => [
                            'alias'  => 'CustomBundle',
                            'prefix' => 'Prefix',
                            'server' => self::WIKI . '/'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::WIKI . '/Prefix/Oro/CustomBundle/Test_run?v=' . self::VERSION
            ],
            'bundle link'                               => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        'OroTestBundle' => [
                            'link' => self::WIKI . '/'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::WIKI . '/'
            ],
            'bundle uri'                                => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        'OroTestBundle' => [
                            'uri' => 'test'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::TEST_WIKI . '/test?v=' . self::VERSION
            ],
            'controller config'                         => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        'OroTestBundle:Test' => [
                            'alias'  => 'MyTest',
                            'prefix' => 'Prefix',
                            'server' => self::WIKI . '/'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::WIKI . '/Prefix/Oro/OroTestBundle/MyTest_run?v=' . self::VERSION
            ],
            'controller link'                           => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        'OroTestBundle:Test' => [
                            'link' => self::WIKI . '/'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::WIKI . '/'
            ],
            'controller uri'                            => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        'OroTestBundle:Test' => [
                            'uri' => 'test'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::TEST_WIKI . '/test?v=' . self::VERSION
            ],
            'action config'                             => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        'OroTestBundle:Test:run' => [
                            'alias'  => 'execute',
                            'prefix' => 'Prefix',
                            'server' => self::WIKI . '/'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::WIKI . '/Prefix/Oro/OroTestBundle/Test_execute?v=' . self::VERSION
            ],
            'action link'                               => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        'OroTestBundle:Test:run' => [
                            'link' => self::WIKI . '/'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::WIKI . '/'
            ],
            'action uri'                                => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        'OroTestBundle:Test:run' => [
                            'uri' => 'test'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::TEST_WIKI . '/test?v=' . self::VERSION
            ],
            'unknown route'                             => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/'
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'unknown_route'
                ],
                'expectedLink'      => self::TEST_WIKI . '?v=' . self::VERSION
            ],
            'annotation link'                           => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route'          => 'test_route',
                    '_' . Help::ALIAS => new Help(['link' => self::WIKI . '/'])
                ],
                'expectedLink'      => self::WIKI . '/'
            ],
            'annotation configuration'                  => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route'          => 'test_route',
                    '_' . Help::ALIAS => new Help(
                        [
                            'actionAlias'     => 'execute',
                            'controllerAlias' => 'Executor',
                            'bundleAlias'     => 'Bundle',
                            'vendorAlias'     => 'Vendor',
                            'prefix'          => 'Prefix',
                            'server'          => self::WIKI . '/'
                        ]
                    )
                ],
                'expectedLink'      => self::WIKI . '/Prefix/Vendor/Bundle/Executor_execute?v=' . self::VERSION
            ],
            'annotation configuration override'         => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route'          => 'test_route',
                    '_' . Help::ALIAS => [
                        new Help(
                            [
                                'actionAlias'     => 'executeFoo',
                                'controllerAlias' => 'ExecutorFoo',
                                'bundleAlias'     => 'BundleFoo',
                                'vendorAlias'     => 'VendorFoo',
                                'prefix'          => 'PrefixFoo',
                                'server'          => self::WIKI . '/foo'
                            ]
                        ),
                        new Help(
                            [
                                'actionAlias'     => 'executeBar',
                                'controllerAlias' => 'ExecutorBar',
                                'bundleAlias'     => 'BundleBar',
                                'vendorAlias'     => 'VendorBar',
                                'prefix'          => 'PrefixBar',
                                'server'          => self::WIKI . '/bar'
                            ]
                        )
                    ]
                ],
                'expectedLink'      => self::WIKI . '/bar/PrefixBar/VendorBar/BundleBar/ExecutorBar_executeBar?v='
                    . self::VERSION
            ],
            'annotation uri'                            => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route'          => 'test_route',
                    '_' . Help::ALIAS => new Help(
                        [
                            'uri'    => 'test',
                            'server' => self::WIKI . '/'
                        ]
                    )
                ],
                'expectedLink'      => self::WIKI . '/test?v=' . self::VERSION
            ],
            'annotation uri unset with resource config' => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        'OroTestBundle:Test:run' => [
                            'uri' => null
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route'          => 'test_route',
                    '_' . Help::ALIAS => new Help(
                        [
                            'uri' => 'test'
                        ]
                    )
                ],
                'expectedLink'      => self::TEST_WIKI . '/Third_Party/Oro/OroTestBundle/Test_run?v=' . self::VERSION
            ],
            'route config'                              => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/'
                    ],
                    'routes'   => [
                        'test_route' => [
                            'action'     => 'execute',
                            'controller' => 'Executor',
                            'bundle'     => 'Bundle',
                            'vendor'     => 'Vendor',
                            'prefix'     => 'Prefix',
                            'server'     => self::WIKI . '/'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::WIKI . '/Prefix/Vendor/Bundle/Executor_execute?v=' . self::VERSION
            ],
            'route uri'                                 => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/'
                    ],
                    'routes'   => [
                        'test_route' => [
                            'uri' => 'test'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::TEST_WIKI . '/test?v=' . self::VERSION
            ],
            'route link'                                => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/'
                    ],
                    'routes'   => [
                        'test_route' => [
                            'link' => self::WIKI . '/test'
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::WIKI . '/test'
            ],
            'route link override by resources'          => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/'
                    ],
                    'routes'    => [
                        'test_route' => [
                            'link' => self::WIKI . '/test'
                        ]
                    ],
                    'resources' => [
                        'OroTestBundle:Test:run' => [
                            'link' => null
                        ]
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route' => 'test_route'
                ],
                'expectedLink'      => self::TEST_WIKI . '/Oro/OroTestBundle/Test_run?v=' . self::VERSION
            ],
            'with parameters'                           => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/'
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route'          => 'test_route',
                    '_' . Help::ALIAS => new Help(
                        ['actionAlias' => 'run/{optionOne}/{option_two}/{option_3}']
                    ),
                    'optionOne'       => 'test1',
                    'option_two'      => 'test2',
                    'option_3'        => 'test3'
                ],
                'expectedLink'      => self::TEST_WIKI . '/Oro/OroTestBundle/Test_run/test1/test2/test3?v='
                    . self::VERSION
            ],
            'with parameters without parameter value'   => [
                'configuration'     => [
                    'defaults' => [
                        'server' => self::TEST_WIKI . '/'
                    ]
                ],
                'controllers'       => [
                    'test_route' => [TestController::class, 'runAction']
                ],
                'requestAttributes' => [
                    '_route'          => 'test_route',
                    '_' . Help::ALIAS => new Help(
                        ['actionAlias' => 'run/{option}']
                    )
                ],
                'expectedLink'      => self::TEST_WIKI . '/Oro/OroTestBundle/Test_run/?v=' . self::VERSION
            ]
        ];
    }
}
