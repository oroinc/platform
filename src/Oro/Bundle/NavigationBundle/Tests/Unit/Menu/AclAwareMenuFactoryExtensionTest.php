<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Menu\AclAwareMenuFactoryExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AclAwareMenuFactoryExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var UrlMatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlMatcher;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ClassAuthorizationChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $classAuthorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var MenuFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->urlMatcher = $this->createMock(UrlMatcherInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->classAuthorizationChecker = $this->createMock(ClassAuthorizationChecker::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $controllerClassProvider = $this->createMock(ControllerClassProvider::class);
        $controllerClassProvider->expects($this->any())
            ->method('getControllers')
            ->willReturn([
                'route_name' => ['controller', 'action'],
            ]);

        $factoryExtension = new AclAwareMenuFactoryExtension(
            $this->urlMatcher,
            $controllerClassProvider,
            $this->authorizationChecker,
            $this->classAuthorizationChecker,
            $this->tokenAccessor,
            $this->logger
        );

        $this->factory = new MenuFactory();
        $this->factory->addExtension($factoryExtension);
    }

    /**
     * @dataProvider optionsWithResourceIdDataProvider
     */
    public function testBuildOptionsWithResourceId(array $options, bool $isAllowed)
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
            ->willReturn($isAllowed);

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertEquals($isAllowed, $item->getExtra('isAllowed'));
    }

    public function optionsWithResourceIdDataProvider(): array
    {
        return [
            'allowed' => [
                ['extras' => ['acl_resource_id' => 'test']],
                true,
            ],
            'not allowed' => [
                ['extras' => ['acl_resource_id' => 'test']],
                false,
            ],
            'allowed with uri' => [
                ['uri' => '#', 'extras' => ['acl_resource_id' => 'test']],
                true,
            ],
            'not allowed with uri' => [
                ['uri' => '#', 'extras' => ['acl_resource_id' => 'test']],
                false,
            ],
            'allowed with route' => [
                ['route' => 'test', 'extras' => ['acl_resource_id' => 'test']],
                true,
            ],
            'not allowed with route' => [
                ['route' => 'test', 'extras' => ['acl_resource_id' => 'test']],
                false,
            ],
            'allowed with route and uri' => [
                ['uri' => '#', 'route' => 'test', 'extras' => ['acl_resource_id' => 'test']],
                true,
            ],
            'not allowed with route and uri' => [
                ['uri' => '#', 'route' => 'test', 'extras' => ['acl_resource_id' => 'test']],
                false,
            ],
        ];
    }

    /**
     * @dataProvider optionsWithoutLoggedUser
     */
    public function testBuildOptionsWithoutLoggedUser(array $options, bool $isAllowed)
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

    public function optionsWithoutLoggedUser(): array
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
            ],
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
     * @dataProvider aclPolicyProvider
     */
    public function testDefaultPolicyOverride(array $options, bool $expected)
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

    public function aclPolicyProvider(): array
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
    public function testBuildOptionsWithEmptyUri(string $uri)
    {
        $options = ['uri' => $uri];

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->urlMatcher->expects($this->never())
            ->method('match');

        $this->classAuthorizationChecker->expects($this->never())
            ->method('isClassMethodGranted');

        $this->logger->expects($this->never())
            ->method('debug');

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertTrue($item->getExtra('isAllowed'));
    }

    public function emptyUriProvider(): array
    {
        return [
            [''],
            ['#'],
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

        $this->urlMatcher->expects($this->once())
            ->method('match')
            ->willThrowException(new ResourceNotFoundException('Route not found'));

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

        $this->classAuthorizationChecker->expects($this->never())
            ->method('isClassMethodGranted');

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertTrue($item->getExtra('isAllowed'));
    }

    /**
     * @dataProvider optionsWithRouteDataProvider
     */
    public function testBuildOptionsWithRoute(array $options, bool $isAllowed, int $callsCount)
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->assertClassAuthorizationCheckerCalls($isAllowed, $callsCount);

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertEquals($isAllowed, $item->getExtra('isAllowed'));
    }

    private function assertClassAuthorizationCheckerCalls(bool $isAllowed, int $callsCount)
    {
        $this->classAuthorizationChecker->expects($this->exactly($callsCount))
            ->method('isClassMethodGranted')
            ->with('controller', 'action')
            ->willReturn($isAllowed);
    }

    public function optionsWithRouteDataProvider(): array
    {
        return [
            'allowed with route' => [
                ['route' => 'route_name'],
                true,
                1,
            ],
            'not allowed with route' => [
                ['route' => 'route_name'],
                false,
                1,
            ],
            'allowed with route and uri' => [
                ['uri' => '#', 'route' => 'route_name'],
                true,
                1,
            ],
            'not allowed with route and uri' => [
                ['uri' => '#', 'route' => 'route_name'],
                false,
                1,
            ],
            'default with route and controller without delimiter' => [
                ['uri' => '#', 'route' => 'test'],
                true,
                0,
            ],
        ];
    }

    /**
     * @dataProvider optionsWithUriDataProvider
     */
    public function testBuildOptionsWithUri(array $options, bool $isAllowed)
    {
        $class = 'controller';
        $method = 'action';

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->urlMatcher->expects($this->once())
            ->method('match')
            ->willReturn(['_controller' => $class . '::' . $method]);

        $this->classAuthorizationChecker->expects($this->once())
            ->method('isClassMethodGranted')
            ->with($class, $method)
            ->willReturn($isAllowed);

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertEquals($isAllowed, $item->getExtra('isAllowed'));
    }

    public function optionsWithUriDataProvider(): array
    {
        return [
            'allowed with route and uri' => [
                ['uri' => '/test'],
                true,
            ],
            'not allowed with route and uri' => [
                ['uri' => '/test'],
                false,
            ],
        ];
    }

    public function testAclCacheByResourceId()
    {
        $options = ['extras' => ['acl_resource_id' => 'resource_id']];

        $this->tokenAccessor->expects(self::exactly(2))
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects(self::exactly(2))
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($options['extras']['acl_resource_id'])
            ->willReturn(true);

        for ($i = 0; $i < 2; $i++) {
            $item = $this->factory->createItem('test', $options);
            self::assertTrue($item->getExtra('isAllowed'));
            self::assertInstanceOf(MenuItem::class, $item);
        }
    }

    public function testAclCacheByKey()
    {
        $options = ['route' => 'route_name'];

        $this->tokenAccessor->expects(self::exactly(2))
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects(self::exactly(2))
            ->method('hasUser')
            ->willReturn(true);

        $this->assertClassAuthorizationCheckerCalls(true, 1);

        $item = $this->factory->createItem('test', $options);
        self::assertTrue($item->getExtra('isAllowed'));
        self::assertInstanceOf(MenuItem::class, $item);

        $options['new_key'] = 'new_value';
        $item = $this->factory->createItem('test', $options);
        self::assertTrue($item->getExtra('isAllowed'));
        self::assertInstanceOf(MenuItem::class, $item);
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

    /**
     * @dataProvider optionsInvocableDataProvider
     */
    public function testBuildOptionsWithInvokableController(array $options, bool $isAllowed)
    {
        $controllerClassProvider = $this->createMock(ControllerClassProvider::class);
        $controllerClassProvider->expects($this->any())
            ->method('getControllers')
            ->willReturn([
                'route_name' => ['controller'],
            ]);

        $factoryExtension = new AclAwareMenuFactoryExtension(
            $this->urlMatcher,
            $controllerClassProvider,
            $this->authorizationChecker,
            $this->classAuthorizationChecker,
            $this->tokenAccessor,
            $this->logger
        );

        $this->factory = new MenuFactory();
        $this->factory->addExtension($factoryExtension);

        $item = $this->factory->createItem('test', $options);
        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertEquals($isAllowed, $item->getExtra('isAllowed'));
    }

    public function optionsInvocableDataProvider(): array
    {
        return [
            'no access check' => [
                [
                    'check_access' => false,
                ],
                true,
            ],
            'show non authorized' => [
                [
                    'extras' => ['show_non_authorized' => true],
                ],
                true,
            ],
            'default' => [
                [],
                false,
            ],
        ];
    }
}
