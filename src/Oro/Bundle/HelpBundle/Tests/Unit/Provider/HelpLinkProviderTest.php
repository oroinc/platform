<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\Provider;

use Oro\Bundle\HelpBundle\Annotation\Help;
use Oro\Bundle\HelpBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\HelpBundle\Provider\HelpLinkProvider;
use Oro\Bundle\HelpBundle\Tests\Unit\Fixtures\Bundles\TestBundle\Controller\TestController;
use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;

class HelpLinkProviderTest extends \PHPUnit\Framework\TestCase
{
    private const VERSION = '1.0';

    private const WIKI = 'http://wiki.test.com';
    private const TEST = 'http://test.com';
    private const TEST_WIKI = self::TEST . '/wiki';

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var ControllerClassProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $controllerClassProvider;

    /** @var VersionHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->controllerClassProvider = $this->createMock(ControllerClassProvider::class);
        $this->helper = $this->createMock(VersionHelper::class);
        $this->cache = $this->createMock(CacheInterface::class);
    }

    private function getHelpLinkProvider(array $defaultConfig = [], array $config = []): HelpLinkProvider
    {
        $configProvider = $this->createMock(ConfigurationProvider::class);
        $configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($config);

        return new HelpLinkProvider(
            $defaultConfig,
            $configProvider,
            $this->requestStack,
            $this->controllerClassProvider,
            $this->helper,
            $this->cache
        );
    }

    public function testGetHelpLinkCached(): void
    {
        $expectedLink = self::TEST . '/help/test?v=1.1';
        $routeName = 'test_route';

        $this->cache->expects(self::once())
            ->method('get')
            ->with($routeName)
            ->willReturn($expectedLink);

        $request = new Request();
        $request->attributes->add(['_route' => $routeName]);
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($request);

        $helpLinkProvider = $this->getHelpLinkProvider();
        self::assertEquals($expectedLink, $helpLinkProvider->getHelpLinkUrl());
    }

    public function testGetHelpLinkWithoutRouteAndWithoutCache(): void
    {
        $expectedLink = 'http://example.com/';

        $this->cache->expects(self::never())
            ->method('get');

        $helpLinkProvider = $this->getHelpLinkProvider(
            ['link' => 'http://example.com/']
        );
        self::assertEquals($expectedLink, $helpLinkProvider->getHelpLinkUrl());
    }

    public function testGetHelpLinkWithoutRouteAndWithCache(): void
    {
        $expectedLink = 'http://example.com/';

        $this->cache->expects(self::never())
            ->method('get');

        $helpLinkProvider = $this->getHelpLinkProvider(
            ['link' => 'http://example.com/']
        );
        self::assertEquals($expectedLink, $helpLinkProvider->getHelpLinkUrl());
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testGetHelpLinkUrl(
        array $configuration,
        array $controllers,
        array $requestAttributes,
        string $expectedLink
    ): void {
        $this->helper->expects(self::any())
            ->method('getVersion')
            ->willReturn(self::VERSION);

        $request = new Request();
        $request->attributes->add($requestAttributes);
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($request);
        $this->controllerClassProvider->expects(self::any())
            ->method('getControllers')
            ->willReturn($controllers);

        if (isset($requestAttributes['_route'])) {
            $this->cache->expects(self::once())
                ->method('get')
                ->with($requestAttributes['_route'])
                ->willReturn($expectedLink);
        }

        $defaultConfig = [];
        if (array_key_exists('defaults', $configuration)) {
            $defaultConfig = $configuration['defaults'];
            unset($configuration['defaults']);
        }
        $helpLinkProvider = $this->getHelpLinkProvider($defaultConfig, $configuration);
        self::assertEquals($expectedLink, $helpLinkProvider->getHelpLinkUrl());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configurationDataProvider(): array
    {
        $myTestController = 'Oro\Bundle\HelpBundle\Controller\MyTestController';
        $myTestControllerRunAction = str_replace('\\', '/', $myTestController) . '_runAction?v=' . self::VERSION;

        $testControllerName = str_replace('\\', '/', TestController::class);
        $testControllerRunAction = $testControllerName . '_runAction?v=' . self::VERSION;
        $testControllerExecuteAction = $testControllerName . '_executeAction?v=' . self::VERSION;

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
                'expectedLink'      => self::TEST_WIKI . '/Oro/' . $testControllerRunAction
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
                'expectedLink'      => self::TEST_WIKI . '/Oro/' . $testControllerRunAction
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
                'expectedLink'      => self::TEST_WIKI . '/Third_Party/Oro/' . $testControllerRunAction
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
                'expectedLink'      => self::WIKI . '/Prefix/CustomVendor/' . $testControllerRunAction
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
            'controller config'                         => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        TestController::class => [
                            'alias'  => $myTestController,
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
                'expectedLink'      => self::WIKI . '/Prefix/Oro/' . $myTestControllerRunAction
            ],
            'controller link'                           => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        TestController::class => [
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
                        TestController::class => [
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
                        TestController::class . '::runAction' => [
                            'alias'  => 'executeAction',
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
                'expectedLink'      => self::WIKI . '/Prefix/Oro/' . $testControllerExecuteAction
            ],
            'action link'                               => [
                'configuration'     => [
                    'defaults'  => [
                        'server' => self::TEST_WIKI . '/',
                        'prefix' => 'Third_Party'
                    ],
                    'resources' => [
                        TestController::class . '::runAction' => [
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
                        TestController::class . '::runAction' => [
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
                            'vendorAlias'     => 'Vendor',
                            'prefix'          => 'Prefix',
                            'server'          => self::WIKI . '/'
                        ]
                    )
                ],
                'expectedLink'      => self::WIKI . '/Prefix/Vendor/Executor_execute?v=' . self::VERSION
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
                                'vendorAlias'     => 'VendorBar',
                                'prefix'          => 'PrefixBar',
                                'server'          => self::WIKI . '/bar'
                            ]
                        )
                    ]
                ],
                'expectedLink'      => self::WIKI . '/bar/PrefixBar/VendorBar/ExecutorBar_executeBar?v='
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
                        TestController::class . '::runAction' => [
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
                'expectedLink'      => self::TEST_WIKI . '/Third_Party/Oro/' . $testControllerRunAction
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
                'expectedLink'      => self::WIKI . '/Prefix/Vendor/Executor_execute?v=' . self::VERSION
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
                        TestController::class . '::runAction' => [
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
                'expectedLink'      => self::TEST_WIKI . '/Oro/' . $testControllerRunAction
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
                        ['actionAlias' => 'runAction/{optionOne}/{option_two}/{option_3}']
                    ),
                    'optionOne'       => 'test1',
                    'option_two'      => 'test2',
                    'option_3'        => 'test3'
                ],
                'expectedLink' => self::TEST_WIKI . '/Oro/' . $testControllerName . '_runAction/test1/test2/test3?v='
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
                        ['actionAlias' => 'runAction/{option}']
                    )
                ],
                'expectedLink' => self::TEST_WIKI . '/Oro/' . $testControllerName . '_runAction/?v=' . self::VERSION
            ]
        ];
    }
}
