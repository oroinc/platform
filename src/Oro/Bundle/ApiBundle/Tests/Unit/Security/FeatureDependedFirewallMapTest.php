<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Security;

use Oro\Bundle\ApiBundle\Security\FeatureDependedFirewallMap;
use Oro\Bundle\ApiBundle\Security\Http\Firewall\FeatureAccessListener;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallContext;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Security\Http\Firewall\AccessListener;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\LogoutListener;

class FeatureDependedFirewallMapTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var FeatureAccessListener|\PHPUnit\Framework\MockObject\MockObject */
    private $featureAccessListener;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->featureAccessListener = $this->createMock(FeatureAccessListener::class);
    }

    private function getFirewallMap(array $map, array $featureDependedFirewalls): FeatureDependedFirewallMap
    {
        return new FeatureDependedFirewallMap(
            $this->container,
            $map,
            $this->featureChecker,
            $this->featureAccessListener,
            $featureDependedFirewalls
        );
    }

    private function setFirewallContextExpectations(
        FirewallContext|\PHPUnit\Framework\MockObject\MockObject $context,
        string $firewallName,
        iterable $listeners,
        object $exceptionListener,
        object $logoutListener
    ): void {
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn(new FirewallConfig($firewallName, 'user_checker'));
        $context->expects(self::once())
            ->method('getListeners')
            ->willReturn($listeners);
        $context->expects(self::once())
            ->method('getExceptionListener')
            ->willReturn($exceptionListener);
        $context->expects(self::once())
            ->method('getLogoutListener')
            ->willReturn($logoutListener);
    }

    public function testGetListenersWithoutContext()
    {
        $request = Request::create('http://localhost');

        $firewallMap = $this->getFirewallMap([], []);
        [$listeners, $exceptionListener, $logoutListener] = $firewallMap->getListeners($request);

        self::assertSame([], $listeners);
        self::assertNull($exceptionListener);
        self::assertNull($logoutListener);
    }

    public function testGetListenersForEmptyFeatureDependedFirewalls()
    {
        $request = Request::create('http://localhost');
        $context = $this->createMock(FirewallContext::class);
        $contextRequestMatcher = $this->createMock(RequestMatcherInterface::class);
        $firewallName = 'firewall1';
        $map = ['security.context' => $contextRequestMatcher];
        $featureDependedFirewalls = [];

        $listeners = [$this->createMock(AccessListener::class)];
        $exceptionListener = $this->createMock(ExceptionListener::class);
        $logoutListener = $this->createMock(LogoutListener::class);

        $contextRequestMatcher->expects(self::once())
            ->method('matches')
            ->with(self::identicalTo($request))
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('security.context')
            ->willReturn($context);
        $this->setFirewallContextExpectations(
            $context,
            $firewallName,
            $listeners,
            $exceptionListener,
            $logoutListener
        );

        $firewallMap = $this->getFirewallMap($map, $featureDependedFirewalls);
        [$actualListeners, $actualExceptionListener, $actualLogoutListener] = $firewallMap->getListeners($request);

        self::assertSame($listeners, $actualListeners);
        self::assertSame($exceptionListener, $actualExceptionListener);
        self::assertSame($logoutListener, $actualLogoutListener);
    }

    public function testGetListenersForNotApiFirewall()
    {
        $request = Request::create('http://localhost');
        $context = $this->createMock(FirewallContext::class);
        $firewallName = 'firewall1';
        $map = ['security.context' => null];
        $featureDependedFirewalls = [
            'firewall2' => [
                'feature_name'               => 'web_api',
                'feature_firewall_listeners' => []
            ]
        ];

        $listeners = [$this->createMock(AccessListener::class)];
        $exceptionListener = $this->createMock(ExceptionListener::class);
        $logoutListener = $this->createMock(LogoutListener::class);

        $this->container->expects(self::once())
            ->method('get')
            ->with('security.context')
            ->willReturn($context);
        $this->setFirewallContextExpectations(
            $context,
            $firewallName,
            $listeners,
            $exceptionListener,
            $logoutListener
        );
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        $firewallMap = $this->getFirewallMap($map, $featureDependedFirewalls);
        [$actualListeners, $actualExceptionListener, $actualLogoutListener] = $firewallMap->getListeners($request);

        self::assertSame($listeners, $actualListeners);
        self::assertSame($exceptionListener, $actualExceptionListener);
        self::assertSame($logoutListener, $actualLogoutListener);
    }

    public function testGetListenersForApiFirewallAndEnabledApiFeature()
    {
        $request = Request::create('http://localhost');
        $context = $this->createMock(FirewallContext::class);
        $firewallName = 'firewall1';
        $apiFeatureName = 'web_api';
        $map = ['security.context' => null];
        $featureDependedFirewalls = [
            $firewallName => [
                'feature_name'               => $apiFeatureName,
                'feature_firewall_listeners' => []
            ]
        ];

        $listeners = [$this->createMock(AccessListener::class)];
        $exceptionListener = $this->createMock(ExceptionListener::class);
        $logoutListener = $this->createMock(LogoutListener::class);

        $this->container->expects(self::once())
            ->method('get')
            ->with('security.context')
            ->willReturn($context);
        $this->setFirewallContextExpectations(
            $context,
            $firewallName,
            $listeners,
            $exceptionListener,
            $logoutListener
        );
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with($apiFeatureName)
            ->willReturn(true);

        $firewallMap = $this->getFirewallMap($map, $featureDependedFirewalls);
        [$actualListeners, $actualExceptionListener, $actualLogoutListener] = $firewallMap->getListeners($request);

        self::assertSame($listeners, $actualListeners);
        self::assertSame($exceptionListener, $actualExceptionListener);
        self::assertSame($logoutListener, $actualLogoutListener);
    }

    public function testGetListenersForApiFirewallAndDisabledApiFeature()
    {
        $request = Request::create('http://localhost');
        $context = $this->createMock(FirewallContext::class);
        $firewallName = 'firewall1';
        $apiFeatureName = 'web_api';
        $map = ['security.context' => null];
        $featureDependedFirewalls = [
            $firewallName => [
                'feature_name'               => $apiFeatureName,
                'feature_firewall_listeners' => []
            ]
        ];

        $listeners = [
            $this->createMock(ContextListener::class),
            $this->createMock(AccessListener::class)
        ];
        $exceptionListener = $this->createMock(ExceptionListener::class);
        $logoutListener = $this->createMock(LogoutListener::class);

        $this->container->expects(self::once())
            ->method('get')
            ->with('security.context')
            ->willReturn($context);
        $this->setFirewallContextExpectations(
            $context,
            $firewallName,
            $listeners,
            $exceptionListener,
            $logoutListener
        );
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with($apiFeatureName)
            ->willReturn(false);

        $firewallMap = $this->getFirewallMap($map, $featureDependedFirewalls);
        [$actualListeners, $actualExceptionListener, $actualLogoutListener] = $firewallMap->getListeners($request);

        self::assertSame([$listeners[0], $this->featureAccessListener, $listeners[1]], $actualListeners);
        self::assertSame($exceptionListener, $actualExceptionListener);
        self::assertSame($logoutListener, $actualLogoutListener);
    }

    public function testGetListenersForApiFirewallAndDisabledApiFeatureAndNoListeners()
    {
        $request = Request::create('http://localhost');
        $context = $this->createMock(FirewallContext::class);
        $firewallName = 'firewall1';
        $apiFeatureName = 'web_api';
        $map = ['security.context' => null];
        $featureDependedFirewalls = [
            $firewallName => [
                'feature_name'               => $apiFeatureName,
                'feature_firewall_listeners' => []
            ]
        ];

        $listeners = [];
        $exceptionListener = $this->createMock(ExceptionListener::class);
        $logoutListener = $this->createMock(LogoutListener::class);

        $this->container->expects(self::once())
            ->method('get')
            ->with('security.context')
            ->willReturn($context);
        $this->setFirewallContextExpectations(
            $context,
            $firewallName,
            $listeners,
            $exceptionListener,
            $logoutListener
        );
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with($apiFeatureName)
            ->willReturn(false);

        $firewallMap = $this->getFirewallMap($map, $featureDependedFirewalls);
        [$actualListeners, $actualExceptionListener, $actualLogoutListener] = $firewallMap->getListeners($request);

        self::assertSame([$this->featureAccessListener], $actualListeners);
        self::assertSame($exceptionListener, $actualExceptionListener);
        self::assertSame($logoutListener, $actualLogoutListener);
    }

    public function testGetListenersForApiFirewallAndDisabledApiFeatureAndHasApiFirewallListeners()
    {
        $request = Request::create('http://localhost');
        $context = $this->createMock(FirewallContext::class);
        $firewallName = 'firewall1';
        $apiFeatureName = 'web_api';
        $map = ['security.context' => null];
        $featureDependedFirewalls = [
            $firewallName => [
                'feature_name'               => $apiFeatureName,
                'feature_firewall_listeners' => [ContextListener::class]
            ]
        ];

        $listeners = [
            $this->createMock(ContextListener::class),
            $this->createMock(AccessListener::class)
        ];
        $exceptionListener = $this->createMock(ExceptionListener::class);
        $logoutListener = $this->createMock(LogoutListener::class);

        $this->container->expects(self::once())
            ->method('get')
            ->with('security.context')
            ->willReturn($context);
        $this->setFirewallContextExpectations(
            $context,
            $firewallName,
            $listeners,
            $exceptionListener,
            $logoutListener
        );
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with($apiFeatureName)
            ->willReturn(false);

        $firewallMap = $this->getFirewallMap($map, $featureDependedFirewalls);
        [$actualListeners, $actualExceptionListener, $actualLogoutListener] = $firewallMap->getListeners($request);

        self::assertSame([$this->featureAccessListener, $listeners[1]], $actualListeners);
        self::assertSame($exceptionListener, $actualExceptionListener);
        self::assertSame($logoutListener, $actualLogoutListener);
    }

    public function testGetListenersForApiFirewallAndDisabledApiFeatureAndNoListenersAndHasApiFirewallListeners()
    {
        $request = Request::create('http://localhost');
        $context = $this->createMock(FirewallContext::class);
        $firewallName = 'firewall1';
        $apiFeatureName = 'web_api';
        $map = ['security.context' => null];
        $featureDependedFirewalls = [
            $firewallName => [
                'feature_name'               => $apiFeatureName,
                'feature_firewall_listeners' => [ContextListener::class]
            ]
        ];

        $listeners = [];
        $exceptionListener = $this->createMock(ExceptionListener::class);
        $logoutListener = $this->createMock(LogoutListener::class);

        $this->container->expects(self::once())
            ->method('get')
            ->with('security.context')
            ->willReturn($context);
        $this->setFirewallContextExpectations(
            $context,
            $firewallName,
            $listeners,
            $exceptionListener,
            $logoutListener
        );
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with($apiFeatureName)
            ->willReturn(false);

        $firewallMap = $this->getFirewallMap($map, $featureDependedFirewalls);
        [$actualListeners, $actualExceptionListener, $actualLogoutListener] = $firewallMap->getListeners($request);

        self::assertSame($listeners, $actualListeners);
        self::assertSame($exceptionListener, $actualExceptionListener);
        self::assertSame($logoutListener, $actualLogoutListener);
    }

    public function testGetListenersForApiFirewallAndDisabledApiFeatureAndNoListenersInGenerator()
    {
        $request = Request::create('http://localhost');
        $context = $this->createMock(FirewallContext::class);
        $firewallName = 'firewall1';
        $apiFeatureName = 'web_api';
        $map = ['security.context' => null];
        $featureDependedFirewalls = [
            $firewallName => [
                'feature_name'               => $apiFeatureName,
                'feature_firewall_listeners' => [ContextListener::class]
            ]
        ];

        $listeners = new RewindableGenerator(
            function () {
            },
            0
        );
        $exceptionListener = $this->createMock(ExceptionListener::class);
        $logoutListener = $this->createMock(LogoutListener::class);

        $this->container->expects(self::once())
            ->method('get')
            ->with('security.context')
            ->willReturn($context);
        $this->setFirewallContextExpectations(
            $context,
            $firewallName,
            $listeners,
            $exceptionListener,
            $logoutListener
        );
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with($apiFeatureName)
            ->willReturn(false);

        $firewallMap = $this->getFirewallMap($map, $featureDependedFirewalls);
        [$actualListeners, $actualExceptionListener, $actualLogoutListener] = $firewallMap->getListeners($request);

        self::assertSame($listeners, $actualListeners);
        self::assertSame($exceptionListener, $actualExceptionListener);
        self::assertSame($logoutListener, $actualLogoutListener);
    }

    public function testGetListenersForApiFirewallAndDisabledApiFeatureAndHasListenersInGenerator()
    {
        $request = Request::create('http://localhost');
        $context = $this->createMock(FirewallContext::class);
        $firewallName = 'firewall1';
        $apiFeatureName = 'web_api';
        $map = ['security.context' => null];
        $featureDependedFirewalls = [
            $firewallName => [
                'feature_name'               => $apiFeatureName,
                'feature_firewall_listeners' => [ContextListener::class]
            ]
        ];

        $listener1 = $this->createMock(ContextListener::class);
        $listener2 = $this->createMock(AccessListener::class);
        $listeners = new RewindableGenerator(
            function () use ($listener1, $listener2) {
                yield $listener1;
                yield $listener2;
            },
            2
        );
        $exceptionListener = $this->createMock(ExceptionListener::class);
        $logoutListener = $this->createMock(LogoutListener::class);

        $this->container->expects(self::once())
            ->method('get')
            ->with('security.context')
            ->willReturn($context);
        $this->setFirewallContextExpectations(
            $context,
            $firewallName,
            $listeners,
            $exceptionListener,
            $logoutListener
        );
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with($apiFeatureName)
            ->willReturn(false);

        $firewallMap = $this->getFirewallMap($map, $featureDependedFirewalls);
        [$actualListeners, $actualExceptionListener, $actualLogoutListener] = $firewallMap->getListeners($request);

        self::assertSame([$this->featureAccessListener, $listener2], $actualListeners);
        self::assertSame($exceptionListener, $actualExceptionListener);
        self::assertSame($logoutListener, $actualLogoutListener);
    }
}
