<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\EventListener\ControllerListener;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\TestDomainObject;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ControllerListenerTest extends \PHPUnit\Framework\TestCase
{
    private const string CLASS_NAME = TestDomainObject::class;
    private const string METHOD_NAME = 'getId';

    private ClassAuthorizationChecker|MockObject $classAuthorizationChecker;
    private LoggerInterface|MockObject $logger;
    private Request $request;
    private ControllerListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->classAuthorizationChecker = $this->createMock(ClassAuthorizationChecker::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->request = new Request();
        $this->request->attributes->add(['_route' => 'test']);

        $this->listener = new ControllerListener(
            $this->classAuthorizationChecker,
            $this->logger
        );
    }

    public function testAccessGranted(): void
    {
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new TestDomainObject(), self::METHOD_NAME],
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (MAIN_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
            ->willReturn(true);

        $this->listener->onKernelController($event);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new TestDomainObject(), self::METHOD_NAME],
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (MAIN_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
            ->willReturn(false);

        $this->listener->onKernelController($event);
    }

    public function testAccessDeniedForSubRequest(): void
    {
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new TestDomainObject(), self::METHOD_NAME],
            $this->request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (SUB_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
            ->willReturn(false);

        $this->listener->onKernelController($event);
    }

    public function testAccessAlreadyChecked(): void
    {
        $this->request->attributes->set('_oro_access_checked', true);
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new TestDomainObject(), self::METHOD_NAME],
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::never())
            ->method('debug');

        $this->classAuthorizationChecker->expects(self::never())
            ->method('isClassMethodGranted');

        $this->listener->onKernelController($event);
    }

    public function testInvokableControllerAccessGranted(): void
    {
        $controller = new class() {
            public function __invoke(): array
            {
                return [];
            }
        };

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(
                self::matchesRegularExpression('/Invoked controller ".*@anonymous.*::__invoke". \(MAIN_REQUEST\)/')
            );

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::anything(), '__invoke')
            ->willReturn(true);

        $this->listener->onKernelController($event);
    }

    public function testInvokableControllerAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $controller = new class() {
            public function __invoke(): array
            {
                return [];
            }
        };

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(self::matchesRegularExpression('/Invoked controller ".*@anonymous.*::__invoke". \(MAIN_REQUEST\)/'));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::anything(), '__invoke')
            ->willReturn(false);

        $this->listener->onKernelController($event);
    }

    public function testInvokableControllerAccessDeniedForSubRequest(): void
    {
        $controller = new class() {
            public function __invoke(): array
            {
                return [];
            }
        };

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $this->request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(self::matchesRegularExpression('/Invoked controller ".*@anonymous.*::__invoke". \(SUB_REQUEST\)/'));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::anything(), '__invoke')
            ->willReturn(false);

        $this->listener->onKernelController($event);
    }

    public function testClosureController(): void
    {
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            static fn () => null,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(self::matchesRegularExpression('/Invoked controller "Closure::__invoke". \(MAIN_REQUEST\)/'));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with('Closure', '__invoke')
            ->willReturn(true);

        $this->listener->onKernelController($event);
    }

    public function testStringController(): void
    {
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            'var_dump',
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::never())
            ->method('debug');

        $this->classAuthorizationChecker->expects(self::never())
            ->method('isClassMethodGranted');

        $this->listener->onKernelController($event);
    }
}
