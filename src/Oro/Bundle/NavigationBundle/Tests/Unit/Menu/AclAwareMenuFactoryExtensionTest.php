<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Doctrine\Common\Cache\CacheProvider;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Menu\AclAwareMenuFactoryExtension;
use Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Stub\UrlGeneratorStub;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AclAwareMenuFactoryExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|Router */
    protected $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ClassAuthorizationChecker */
    protected $classAuthorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var MenuFactory */
    protected $factory;

    /** @var AclAwareMenuFactoryExtension */
    protected $factoryExtension;

    /** @var CacheProvider */
    protected $cache;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    protected function setUp()
    {
        $this->router = $this->createMock(Router::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->classAuthorizationChecker = $this->createMock(ClassAuthorizationChecker::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->factoryExtension = new AclAwareMenuFactoryExtension(
            $this->router,
            $this->authorizationChecker,
            $this->classAuthorizationChecker,
            $this->tokenAccessor,
            $this->logger
        );

        $this->factory = new MenuFactory();
        $this->factory->addExtension($this->factoryExtension);
    }

    /**
     * @dataProvider optionsWithResourceIdDataProvider
     * @param array $options
     * @param boolean $isAllowed
     */
    public function testBuildOptionsWithResourceId($options, $isAllowed)
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($options['extras']['acl_resource_id'])
            ->will($this->returnValue($isAllowed));

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertEquals($isAllowed, $item->getExtra('isAllowed'));
    }

    /**
     * @return array
     */
    public function optionsWithResourceIdDataProvider()
    {
        return [
            'allowed' => [
                ['extras' => ['acl_resource_id' => 'test']],
                true
            ],
            'not allowed' => [
                ['extras' => ['acl_resource_id' => 'test']],
                false
            ],
            'allowed with uri' => [
                ['uri' => '#', 'extras' => ['acl_resource_id' => 'test']],
                true
            ],
            'not allowed with uri' => [
                ['uri' => '#', 'extras' => ['acl_resource_id' => 'test']],
                false
            ],
            'allowed with route' => [
                ['route' => 'test', 'extras' => ['acl_resource_id' => 'test']],
                true
            ],
            'not allowed with route' => [
                ['route' => 'test', 'extras' => ['acl_resource_id' => 'test']],
                false
            ],
            'allowed with route and uri' => [
                ['uri' => '#', 'route' => 'test', 'extras' => ['acl_resource_id' => 'test']],
                true
            ],
            'not allowed with route and uri' => [
                ['uri' => '#', 'route' => 'test', 'extras' => ['acl_resource_id' => 'test']],
                false
            ],
        ];
    }

    /**
     * @param array   $options
     * @param boolean $isAllowed
     *
     * @dataProvider optionsWithoutLoggedUser
     */
    public function testBuildOptionsWithoutLoggedUser($options, $isAllowed)
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->any())
            ->method('hasUser')
            ->willReturn(false);

        $item = $this->factory->createItem('test', $options);

        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertEquals($isAllowed, $item->getExtra('isAllowed'));
    }

    /**
     * @return array
     */
    public function optionsWithoutLoggedUser()
    {
        return [
            'show non authorized' => [
                ['extras' => ['show_non_authorized' => true]],
                true,
            ],
            'do not show non authorized' => [
                ['extras' => []],
                false,
            ],
            'do not check access' => [
                ['check_access' => false, 'extras' => []],
                true,
            ],
            'check access' => [
                ['check_access_not_logged_in' => true, 'extras' => []],
                true,
            ]
        ];
    }

    public function testBuildOptionsWithRouteNotFound()
    {
        $options = ['route' => 'no-route'];

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $generator = new UrlGeneratorStub();
        $this->router->expects($this->once())
            ->method('getGenerator')
            ->willReturn($generator);

        $this->classAuthorizationChecker->expects($this->never())
            ->method('isClassMethodGranted');

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertTrue($item->getExtra('isAllowed'));
    }

    public function testBuildOptionsAlreadyProcessed()
    {
        $options = [
            'extras' => [
                'isAllowed' => false,
            ],
        ];

        $this->tokenAccessor->expects($this->never())
            ->method('getToken');
        $this->tokenAccessor->expects($this->never())
            ->method('hasUser');

        $this->factory->createItem('test', $options);
    }

    /**
     * @param array $options
     * @param bool $expected
     *
     * @dataProvider aclPolicyProvider
     */
    public function testDefaultPolicyOverride(array $options, $expected)
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertEquals($expected, $item->getExtra('isAllowed'));
    }

    /**
     * @return array
     */
    public function aclPolicyProvider()
    {
        return [
            [[], true],
            [['extras' => []], true],
            [['extras' => ['acl_policy' => true]], true],
            [['extras' => ['acl_policy' => false]], false],
        ];
    }

    /**
     * @dataProvider emptyUriProvider
     */
    public function testBuildOptionsWithEmptyUri($uri)
    {
        $options = ['uri' => $uri];

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->router->expects($this->never())
            ->method('match');

        $this->classAuthorizationChecker->expects($this->never())
            ->method('isClassMethodGranted');

        $this->logger->expects($this->never())
            ->method('debug');

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertTrue($item->getExtra('isAllowed'));
    }

    public function emptyUriProvider()
    {
        return [
            [''],
            ['#']
        ];
    }

    public function testBuildOptionsWithUnknownUri()
    {
        $options = ['uri' => 'unknown'];

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->router->expects($this->once())
            ->method('match')
            ->will($this->throwException(new ResourceNotFoundException('Route not found')));

        $this->classAuthorizationChecker->expects($this->never())
            ->method('isClassMethodGranted');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Route not found', ['pathinfo' => 'unknown']);

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertTrue($item->getExtra('isAllowed'));
    }

    public function testBuildOptionsWithEmptyRoute()
    {
        $options = ['route' => ''];

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->router->expects($this->never())
            ->method('getGenerator');

        $this->classAuthorizationChecker->expects($this->never())
            ->method('isClassMethodGranted');

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertTrue($item->getExtra('isAllowed'));
    }

    /**
     * @dataProvider optionsWithRouteDataProvider
     * @param array   $options
     * @param boolean $isAllowed
     * @param int     $callsCount
     */
    public function testBuildOptionsWithRoute($options, $isAllowed, $callsCount)
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $generator = new UrlGeneratorStub();
        $this->router->expects($this->once())
            ->method('getGenerator')
            ->willReturn($generator);

        $this->assertClassAuthorizationCheckerCalls($isAllowed, $callsCount);

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertEquals($isAllowed, $item->getExtra('isAllowed'));
    }

    /**
     * @param boolean $isAllowed
     * @param int     $callsCount
     */
    private function assertClassAuthorizationCheckerCalls($isAllowed, $callsCount)
    {
        $this->classAuthorizationChecker->expects($this->exactly($callsCount))
            ->method('isClassMethodGranted')
            ->with('controller', 'action')
            ->will($this->returnValue($isAllowed));
    }

    /**
     * @return array
     */
    public function optionsWithRouteDataProvider()
    {
        return [
            'allowed with route' => [
                ['route' => 'route_name'], true, 1
            ],
            'not allowed with route' => [
                ['route' => 'route_name'], false, 1
            ],
            'allowed with route and uri' => [
                ['uri' => '#', 'route' => 'route_name'], true, 1
            ],
            'not allowed with route and uri' => [
                ['uri' => '#', 'route' => 'route_name'], false, 1
            ],
            'default with route and controller without delimiter' => [
                ['uri' => '#', 'route' => 'test'], true, 0
            ],
        ];
    }

    /**
     * @dataProvider optionsWithUriDataProvider
     * @param array   $options
     * @param boolean $isAllowed
     */
    public function testBuildOptionsWithUri($options, $isAllowed)
    {
        $class = 'controller';
        $method = 'action';

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->router->expects($this->once())
            ->method('match')
            ->will($this->returnValue(['_controller' => $class . '::' . $method]));

        $this->classAuthorizationChecker->expects($this->once())
            ->method('isClassMethodGranted')
            ->with($class, $method)
            ->will($this->returnValue($isAllowed));

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertEquals($isAllowed, $item->getExtra('isAllowed'));
    }

    /**
     * @return array
     */
    public function optionsWithUriDataProvider()
    {
        return [
            'allowed with route and uri' => [
                ['uri' => '/test'], true
            ],
            'not allowed with route and uri' => [
                ['uri' => '/test'], false
            ],
        ];
    }

    public function testAclCacheByResourceId()
    {
        $options = ['extras' => ['acl_resource_id' => 'resource_id']];

        $this->tokenAccessor->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->exactly(2))
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($options['extras']['acl_resource_id'])
            ->will($this->returnValue(true));

        for ($i = 0; $i < 2; $i++) {
            $item = $this->factory->createItem('test', $options);
            $this->assertTrue($item->getExtra('isAllowed'));
            $this->assertInstanceOf(MenuItem::class, $item);
        }

        $this->assertAttributeCount(1, 'existingAclChecks', $this->factoryExtension);
        $this->assertAttributeEquals(
            [$options['extras']['acl_resource_id'] => true],
            'existingAclChecks',
            $this->factoryExtension
        );
    }

    public function testAclCacheByKey()
    {
        $options = ['route' => 'route_name'];

        $this->tokenAccessor->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->exactly(2))
            ->method('hasUser')
            ->willReturn(true);

        $generator = new UrlGeneratorStub();
        $this->router->expects($this->once())
            ->method('getGenerator')
            ->willReturn($generator);

        $this->assertClassAuthorizationCheckerCalls(true, 1);

        $item = $this->factory->createItem('test', $options);
        $this->assertTrue($item->getExtra('isAllowed'));
        $this->assertInstanceOf(MenuItem::class, $item);

        $options['new_key'] = 'new_value';
        $item = $this->factory->createItem('test', $options);
        $this->assertTrue($item->getExtra('isAllowed'));
        $this->assertInstanceOf(MenuItem::class, $item);

        $this->assertAttributeCount(1, 'existingAclChecks', $this->factoryExtension);
        $this->assertAttributeEquals(['controller::action' => true], 'existingAclChecks', $this->factoryExtension);
    }

    public function testBuildOptionsWithoutToken()
    {
        $options = ['route' => 'no-route', 'check_access_not_logged_in' => true];

        $this->tokenAccessor->expects($this->never())
            ->method('hasUser');

        $this->tokenAccessor->expects($this->any())
            ->method('getToken')
            ->willReturn(null);

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertTrue($item->getExtra('isAllowed'));
    }
}
