<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker;
use Oro\Bundle\SecurityBundle\EventListener\ControllerListener;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\TestDomainObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ControllerListenerTest extends TestCase
{
    private const string CLASS_NAME = TestDomainObject::class;
    private const string METHOD_NAME = 'getId';

    private ClassAuthorizationChecker|MockObject $classAuthorizationChecker;
    private RequestAuthorizationChecker|MockObject $requestAuthorizationChecker;
    private LoggerInterface|MockObject $logger;
    private Request $request;
    private ControllerListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->classAuthorizationChecker = $this->createMock(ClassAuthorizationChecker::class);
        $this->requestAuthorizationChecker = $this->createMock(RequestAuthorizationChecker::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->request = new Request();
        $this->request->attributes->add(['_route' => 'test']);

        $this->listener = new ControllerListener(
            $this->classAuthorizationChecker,
            $this->logger,
            $this->requestAuthorizationChecker
        );
    }

    public function testAccessGranted(): void
    {
        $event = new ControllerArgumentsEvent(
            kernel:  $this->createMock(HttpKernelInterface::class),
            controller: [new TestDomainObject(), self::METHOD_NAME],
            arguments: [],
            request:  $this->request,
            requestType:  HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (MAIN_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
            ->willReturn(true);

        $this->listener->onKernelControllerArguments($event);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: [new TestDomainObject(), self::METHOD_NAME],
            arguments: [],
            request: $this->request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (MAIN_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
            ->willReturn(false);

        $this->listener->onKernelControllerArguments($event);
    }

    public function testAccessDeniedForSubRequest(): void
    {
        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: [new TestDomainObject(), self::METHOD_NAME],
            arguments: [],
            request: $this->request,
            requestType: HttpKernelInterface::SUB_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (SUB_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
            ->willReturn(false);

        $this->listener->onKernelControllerArguments($event);
    }

    public function testInvokableControllerAccessGranted(): void
    {
        $controller = new class () {
            public function __invoke(): array
            {
                return [];
            }
        };

        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: $controller,
            arguments: [],
            request: $this->request,
            requestType: HttpKernelInterface::MAIN_REQUEST
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

        $this->listener->onKernelControllerArguments($event);
    }

    public function testInvokableControllerAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $controller = new class () {
            public function __invoke(): array
            {
                return [];
            }
        };

        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: $controller,
            arguments: [],
            request: $this->request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(self::matchesRegularExpression('/Invoked controller ".*@anonymous.*::__invoke". \(MAIN_REQUEST\)/'));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::anything(), '__invoke')
            ->willReturn(false);

        $this->listener->onKernelControllerArguments($event);
    }

    public function testInvokableControllerAccessDeniedForSubRequest(): void
    {
        $controller = new class () {
            public function __invoke(): array
            {
                return [];
            }
        };

        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: $controller,
            arguments: [],
            request: $this->request,
            requestType: HttpKernelInterface::SUB_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(self::matchesRegularExpression('/Invoked controller ".*@anonymous.*::__invoke". \(SUB_REQUEST\)/'));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::anything(), '__invoke')
            ->willReturn(false);

        $this->listener->onKernelControllerArguments($event);
    }

    public function testClosureController(): void
    {
        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: static fn () => null,
            arguments: [],
            request: $this->request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(self::matchesRegularExpression('/Invoked controller "Closure::__invoke". \(MAIN_REQUEST\)/'));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with('Closure', '__invoke')
            ->willReturn(true);

        $this->listener->onKernelControllerArguments($event);
    }

    public function testStringFunctionController(): void
    {
        // This is just a sanity check, as in Symfony we wouldn't use a string function controller
        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: 'var_dump',
            arguments: [],
            request: $this->request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::never())
            ->method('debug');

        $this->classAuthorizationChecker->expects(self::never())
            ->method('isClassMethodGranted');

        $this->listener->onKernelControllerArguments($event);
    }

    public function testEntityAccessGranted(): void
    {
        $testObject = new TestDomainObject();
        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: [new TestDomainObject(), self::METHOD_NAME],
            arguments: [$testObject],
            request: $this->request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $this->requestAuthorizationChecker->expects(self::once())
            ->method('isRequestObjectIsGranted')
            ->with($this->request, $testObject)
            ->willReturn(1);

        $this->logger->expects(self::never())
            ->method('debug');

        $this->classAuthorizationChecker->expects(self::never())
            ->method('isClassMethodGranted');

        $this->listener->onKernelControllerArguments($event);

        self::assertTrue($this->request->attributes->get('_oro_access_checked'));
    }

    public function testEntityAccessDenied(): void
    {
        $testObject = new TestDomainObject();
        $acl = Acl::fromArray([
            'id' => 1,
            'type' => 'entity',
            'class' => TestDomainObject::class,
            'permission' => 'EDIT'
        ]);

        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: [new TestDomainObject(), self::METHOD_NAME],
            arguments: [$testObject],
            request: $this->request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $this->requestAuthorizationChecker->expects(self::once())
            ->method('isRequestObjectIsGranted')
            ->with($this->request, $testObject)
            ->willReturn(-1);

        $this->requestAuthorizationChecker->expects(self::once())
            ->method('getRequestAcl')
            ->with($this->request)
            ->willReturn($acl);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You do not get EDIT permission for this object');

        $this->listener->onKernelControllerArguments($event);
    }

    public function testEntityAccessNotChecked(): void
    {
        $testObject = new TestDomainObject();
        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: [new TestDomainObject(), self::METHOD_NAME],
            arguments: [$testObject],
            request: $this->request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $this->requestAuthorizationChecker->expects(self::once())
            ->method('isRequestObjectIsGranted')
            ->with($this->request, $testObject)
            ->willReturn(0);

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (MAIN_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
            ->willReturn(true);

        $this->listener->onKernelControllerArguments($event);

        self::assertFalse($this->request->attributes->get('_oro_access_checked'));
    }

    public function testEntityAccessWithMultipleArguments(): void
    {
        $testObject1 = new TestDomainObject();
        $testObject2 = new TestDomainObject();
        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: [new TestDomainObject(), self::METHOD_NAME],
            arguments: [$testObject1, $this->request, $testObject2],
            request: $this->request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $this->requestAuthorizationChecker->expects(self::exactly(2))
            ->method('isRequestObjectIsGranted')
            ->withConsecutive(
                [$this->request, $testObject1],
                [$this->request, $testObject2]
            )
            ->willReturnOnConsecutiveCalls(0, 1);

        $this->logger->expects(self::never())
            ->method('debug');

        $this->classAuthorizationChecker->expects(self::never())
            ->method('isClassMethodGranted');

        $this->listener->onKernelControllerArguments($event);

        self::assertTrue($this->request->attributes->get('_oro_access_checked'));
    }

    public function testEntityAccessWithoutRequestAuthorizationChecker(): void
    {
        $listener = new ControllerListener(
            $this->classAuthorizationChecker,
            $this->logger
        );

        $testObject = new TestDomainObject();
        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: [new TestDomainObject(), self::METHOD_NAME],
            arguments: [$testObject],
            request: $this->request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (MAIN_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
            ->willReturn(true);

        $listener->onKernelControllerArguments($event);

        self::assertFalse($this->request->attributes->get('_oro_access_checked'));
    }

    public function testEntityAccessWithRequestArgument(): void
    {
        $event = new ControllerArgumentsEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            controller: [new TestDomainObject(), self::METHOD_NAME],
            arguments: [$this->request],
            request: $this->request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $this->requestAuthorizationChecker->expects(self::never())
            ->method('isRequestObjectIsGranted');

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (MAIN_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
            ->willReturn(true);

        $this->listener->onKernelControllerArguments($event);

        self::assertFalse($this->request->attributes->get('_oro_access_checked'));
    }
}
