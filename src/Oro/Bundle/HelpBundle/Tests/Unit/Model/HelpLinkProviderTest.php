<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\Model;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\HelpBundle\Annotation\Help;
use Oro\Bundle\HelpBundle\Model\HelpLinkProvider;
use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class HelpLinkProviderTest extends \PHPUnit\Framework\TestCase
{
    private const VERSION = '1.0';

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var ControllerNameParser|\PHPUnit\Framework\MockObject\MockObject */
    private $parser;

    /** @var VersionHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var HelpLinkProvider */
    private $provider;
    
    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->parser = $this->createMock(ControllerNameParser::class);
        $this->helper = $this->createMock(VersionHelper::class);
        $this->cache = $this->createMock(CacheProvider::class);

        $this->provider = new HelpLinkProvider($this->requestStack, $this->parser, $this->helper, $this->cache);
    }

    public function testGetHelpLinkCached()
    {
        $expectedLink = 'http://test.com/help/test?v=1.1';
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

        $this->assertEquals($expectedLink, $this->provider->getHelpLinkUrl());
    }

    public function testGetHelpLinkWithoutRouteAndWithoutCache()
    {
        $expectedLink = 'http://example.com/';

        $this->cache->expects($this->never())
            ->method('fetch');
        $this->cache->expects($this->never())
            ->method('save');

        $this->provider->setConfiguration([
            'defaults' => [
                'link' => 'http://example.com/',
            ]
        ]);
        $this->assertEquals($expectedLink, $this->provider->getHelpLinkUrl());
    }

    public function testGetHelpLinkWithoutRouteAndWithCache()
    {
        $expectedLink = 'http://example.com/';

        $this->cache->expects($this->never())
            ->method('fetch');
        $this->cache->expects($this->never())
            ->method('save');

        $this->provider->setConfiguration([
            'defaults' => [
                'link' => 'http://example.com/',
            ]
        ]);
        $this->assertEquals($expectedLink, $this->provider->getHelpLinkUrl());
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testGetHelpLinkUrl(
        array $configuration,
        array $requestAttributes,
        array $parserResults,
        string $expectedLink
    ) {
        if (isset($parserResults['buildResult'])) {
            $this->assertArrayHasKey('_controller', $requestAttributes);
            $this->parser->expects($this->once())
                ->method('build')
                ->with($requestAttributes['_controller'])
                ->will($this->returnValue($parserResults['buildResult']));
        } elseif (isset($parserResults['parseResult'])) {
            $this->assertArrayHasKey('_controller', $requestAttributes);
            $this->parser->expects($this->once())
                ->method('parse')
                ->with($requestAttributes['_controller'])
                ->will($this->returnValue($parserResults['parseResult']));
        } else {
            $this->parser->expects($this->never())->method($this->anything());
        }

        $this->helper
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue(self::VERSION));

        $this->provider->setConfiguration($configuration);

        $request = new Request();
        $request->attributes->add($requestAttributes);
        $this->requestStack->expects($this->any())
            ->method('getMasterRequest')
            ->willReturn($request);

        if (isset($requestAttributes['_route'])) {
            $this->cache->expects($this->once())
                ->method('fetch')
                ->with($requestAttributes['_route'])
                ->willReturn(false);
            $this->cache->expects($this->once())
                ->method('save')
                ->with($requestAttributes['_route'], $expectedLink);
        }

        $this->assertEquals($expectedLink, $this->provider->getHelpLinkUrl());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function configurationDataProvider()
    {
        return array(
            'simple default no cache' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/'
                    )
                ),
                'requestAttributes' => array(
                    '_controller' => 'Acme\DemoBundle\Controller\TestController::runAction',
                    '_route' => 'test_route'
                ),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://test.com/wiki/Acme/AcmeDemoBundle/Test_run?v=' . self::VERSION
            ),
            'simple default with cache' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/'
                    )
                ),
                'requestAttributes' => array(
                    '_controller' => 'Acme\DemoBundle\Controller\TestController::runAction',
                    '_route' => 'test_route'
                ),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://test.com/wiki/Acme/AcmeDemoBundle/Test_run?v=' . self::VERSION
            ),
            'simple default with controller short name' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/'
                    )
                ),
                'requestAttributes' => array(
                    '_controller' => 'AcmeDemoBundle:Test:run'
                ),
                'parserResults' => array('parseResult' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'expectedLink' => 'http://test.com/wiki/Acme/AcmeDemoBundle/Test_run?v=' . self::VERSION
            ),
            'default with prefix' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://test.com/wiki/Third_Party/Acme/AcmeDemoBundle/Test_run?v='
                    . self::VERSION
            ),
            'default with link' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party',
                        'link' => 'http://wiki.test.com/'
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/'
            ),
            'vendor link' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'vendors' => array(
                        'Acme' => array(
                            'link' => 'http://wiki.test.com/'
                        )
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/'
            ),
            'vendor config' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'vendors' => array(
                        'Acme' => array(
                            'alias' => 'CustomVendor',
                            'prefix' => 'Prefix',
                            'server' => 'http://wiki.test.com/'
                        )
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/Prefix/CustomVendor/AcmeDemoBundle/Test_run?v='
                    . self::VERSION
            ),
            'vendor uri' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'vendors' => array(
                        'Acme' => array(
                            'uri' => 'test'
                        )
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://test.com/wiki/test?v=' . self::VERSION
            ),
            'bundle config' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'resources' => array(
                        'AcmeDemoBundle' => array(
                            'alias' => 'CustomBundle',
                            'prefix' => 'Prefix',
                            'server' => 'http://wiki.test.com/'
                        )
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/Prefix/Acme/CustomBundle/Test_run?v='
                    . self::VERSION
            ),
            'bundle link' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'resources' => array(
                        'AcmeDemoBundle' => array(
                            'link' => 'http://wiki.test.com/'
                        )
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/'
            ),
            'bundle uri' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'resources' => array(
                        'AcmeDemoBundle' => array(
                            'uri' => 'test'
                        )
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://test.com/wiki/test?v=' . self::VERSION
            ),
            'controller config' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'resources' => array(
                        'AcmeDemoBundle:Test' => array(
                            'alias' => 'MyTest',
                            'prefix' => 'Prefix',
                            'server' => 'http://wiki.test.com/'
                        )
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/Prefix/Acme/AcmeDemoBundle/MyTest_run?v='
                    . self::VERSION
            ),
            'controller link' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'resources' => array(
                        'AcmeDemoBundle:Test' => array(
                            'link' => 'http://wiki.test.com/'
                        )
                    )
                ),

                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/'
            ),
            'controller uri' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'resources' => array(
                        'AcmeDemoBundle:Test' => array(
                            'uri' => 'test'
                        )
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://test.com/wiki/test?v=' . self::VERSION
            ),
            'action config' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'resources' => array(
                        'AcmeDemoBundle:Test:run' => array(
                            'alias' => 'execute',
                            'prefix' => 'Prefix',
                            'server' => 'http://wiki.test.com/'
                        )
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/Prefix/Acme/AcmeDemoBundle/Test_execute?v='
                    . self::VERSION
            ),
            'action link' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'resources' => array(
                        'AcmeDemoBundle:Test:run' => array(
                            'link' => 'http://wiki.test.com/'
                        )
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/'
            ),
            'action uri' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'resources' => array(
                        'AcmeDemoBundle:Test:run' => array(
                            'uri' => 'test'
                        )
                    )
                ),
                'requestAttributes' => array('_controller' => 'Acme\DemoBundle\Controller\TestController::runAction'),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://test.com/wiki/test?v=' . self::VERSION
            ),
            'service id controller' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/'
                    )
                ),
                'requestAttributes' => array('_controller' => 'controller_service:runAction'),
                'parserResults' => array(),
                'expectedLink' => 'http://test.com/wiki?v=' . self::VERSION
            ),
            'annotation link' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    )
                ),
                'requestAttributes' => array(
                    '_controller' => 'Acme\DemoBundle\Controller\TestController::runAction',
                    '_' . Help::ALIAS => new Help(array('link' => 'http://wiki.test.com/'))
                ),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/'
            ),
            'annotation configuration' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    )
                ),
                'requestAttributes' => array(
                    '_controller' => 'Acme\DemoBundle\Controller\TestController::runAction',
                    '_' . Help::ALIAS => new Help(
                        array(
                            'actionAlias' => 'execute',
                            'controllerAlias' => 'Executor',
                            'bundleAlias' => 'Bundle',
                            'vendorAlias' => 'Vendor',
                            'prefix' => 'Prefix',
                            'server' => 'http://wiki.test.com/'
                        )
                    )
                ),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/Prefix/Vendor/Bundle/Executor_execute?v='
                    . self::VERSION
            ),
            'annotation configuration override' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    )
                ),
                'requestAttributes' => array(
                    '_controller' => 'Acme\DemoBundle\Controller\TestController::runAction',
                    '_' . Help::ALIAS => array(
                        new Help(
                            array(
                                'actionAlias' => 'executeFoo',
                                'controllerAlias' => 'ExecutorFoo',
                                'bundleAlias' => 'BundleFoo',
                                'vendorAlias' => 'VendorFoo',
                                'prefix' => 'PrefixFoo',
                                'server' => 'http://wiki.test.com/foo'
                            )
                        ),
                        new Help(
                            array(
                                'actionAlias' => 'executeBar',
                                'controllerAlias' => 'ExecutorBar',
                                'bundleAlias' => 'BundleBar',
                                'vendorAlias' => 'VendorBar',
                                'prefix' => 'PrefixBar',
                                'server' => 'http://wiki.test.com/bar'
                            )
                        )
                    )
                ),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/bar/PrefixBar/VendorBar/BundleBar/ExecutorBar_executeBar?v='
                    . self::VERSION
            ),
            'annotation uri' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    )
                ),
                'requestAttributes' => array(
                    '_controller' => 'Acme\DemoBundle\Controller\TestController::runAction',
                    '_' . Help::ALIAS => new Help(
                        array(
                            'uri' => 'test',
                            'server' => 'http://wiki.test.com/'
                        )
                    ),
                ),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://wiki.test.com/test?v=' . self::VERSION
            ),
            'annotation uri unset with resource config' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/',
                        'prefix' => 'Third_Party'
                    ),
                    'resources' => array(
                        'AcmeDemoBundle:Test:run' => array(
                            'uri' => null,
                        )
                    )
                ),
                'requestAttributes' => array(
                    '_controller' => 'Acme\DemoBundle\Controller\TestController::runAction',
                    '_' . Help::ALIAS => new Help(
                        array(
                            'uri' => 'test',
                        )
                    ),
                ),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://test.com/wiki/Third_Party/Acme/AcmeDemoBundle/Test_run?v='
                    . self::VERSION
            ),
            'route config' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/'
                    ),
                    'routes' => array(
                        'test_route' => array(
                            'action' => 'execute',
                            'controller' => 'Executor',
                            'bundle' => 'Bundle',
                            'vendor' => 'Vendor',
                            'prefix' => 'Prefix',
                            'server' => 'http://wiki.test.com/'
                        )
                    )
                ),
                'requestAttributes' => array('_route' => 'test_route'),
                'parserResults' => array(),
                'expectedLink' => 'http://wiki.test.com/Prefix/Vendor/Bundle/Executor_execute?v='
                    . self::VERSION
            ),
            'route uri' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/'
                    ),
                    'routes' => array(
                        'test_route' => array(
                            'uri' => 'test'
                        )
                    )
                ),
                'requestAttributes' => array('_route' => 'test_route'),
                'parserResults' => array(),
                'expectedLink' => 'http://test.com/wiki/test?v=' . self::VERSION
            ),
            'route link' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/'
                    ),
                    'routes' => array(
                        'test_route' => array(
                            'link' => 'http://wiki.test.com/test'
                        )
                    )
                ),
                'requestAttributes' => array('_route' => 'test_route'),
                'parserResults' => array(),
                'expectedLink' => 'http://wiki.test.com/test'
            ),
            'route link override by resources' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/'
                    ),
                    'routes' => array(
                        'test_route' => array(
                            'link' => 'http://wiki.test.com/test'
                        )
                    ),
                    'resources' => array(
                        'AcmeDemoBundle:Test:run' => array(
                            'link' => null
                        )
                    )
                ),
                'requestAttributes' => array(
                    '_controller' => 'Acme\DemoBundle\Controller\TestController::runAction',
                    '_route' => 'test_route'
                ),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://test.com/wiki/Acme/AcmeDemoBundle/Test_run?v=' . self::VERSION
            ),
            'with parameters' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/'
                    )
                ),
                'requestAttributes' => array(
                    '_controller' => 'Acme\DemoBundle\Controller\TestController::runAction',
                    '_' . Help::ALIAS => new Help(
                        array('actionAlias' => 'run/{optionOne}/{option_two}/{option_3}')
                    ),
                    'optionOne' => 'test1',
                    'option_two' => 'test2',
                    'option_3' => 'test3'
                ),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://test.com/wiki/Acme/AcmeDemoBundle/Test_run/test1/test2/test3?v='
                    . self::VERSION
            ),
            'with parameters without parameter value' => array(
                'configuration' => array(
                    'defaults' => array(
                        'server' => 'http://test.com/wiki/'
                    )
                ),
                'requestAttributes' => array(
                    '_controller' => 'Acme\DemoBundle\Controller\TestController::runAction',
                    '_' . Help::ALIAS => new Help(
                        array('actionAlias' => 'run/{option}')
                    )
                ),
                'parserResults' => array('buildResult' => 'AcmeDemoBundle:Test:run'),
                'expectedLink' => 'http://test.com/wiki/Acme/AcmeDemoBundle/Test_run/?v=' . self::VERSION
            ),
        );
    }
}
