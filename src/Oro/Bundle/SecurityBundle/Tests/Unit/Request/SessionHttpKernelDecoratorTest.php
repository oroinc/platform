<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Request;

use Oro\Bundle\SecurityBundle\Request\SessionHttpKernelDecorator;
use Oro\Bundle\SecurityBundle\Request\SessionStorageOptionsManipulator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SessionHttpKernelDecoratorTest extends TestCase
{
    private const array SESSION_OPTIONS = [
        'name' => 'CONSOLE',
        'cookie_path' => '/console',
        'cookie_lifetime' => 10,
    ];

    private HttpKernel&MockObject $kernel;
    private ContainerStub|ContainerInterface $container;
    private SessionHttpKernelDecorator $kernelDecorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->kernel = $this->createMock(HttpKernel::class);
        $this->container = new ContainerStub([
            'oro_security.session.storage.options' => self::SESSION_OPTIONS,
            'session.storage.options' => self::SESSION_OPTIONS,
        ]);
        $sessionStorageOptionsManipulator = new SessionStorageOptionsManipulator($this->container);

        $this->kernelDecorator = new SessionHttpKernelDecorator($this->kernel, $sessionStorageOptionsManipulator);
    }

    public function testTerminate(): void
    {
        $request = Request::create('http://localhost/admin/test.php');
        $response = $this->createMock(Response::class);

        $this->kernel->expects(self::once())
            ->method('terminate')
            ->with(self::identicalTo($request), self::identicalTo($response));

        $this->kernelDecorator->terminate($request, $response);
    }

    public function testHandleForApplicationInRootDir(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getBasePath')
            ->willReturn('/');
        $type = HttpKernelInterface::MAIN_REQUEST;
        $catch = true;
        $response = $this->createMock(Response::class);

        $this->kernel->expects(self::once())
            ->method('handle')
            ->with(self::identicalTo($request), $type, $catch)
            ->willReturn($response);

        self::assertSame(
            $response,
            $this->kernelDecorator->handle($request, $type, $catch)
        );

        self::assertEquals(
            self::SESSION_OPTIONS,
            $this->container->getParameter('session.storage.options')
        );
    }

    public function testHandleForApplicationInSubDir(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getBasePath')
            ->willReturn('/subDir');
        $type = HttpKernelInterface::MAIN_REQUEST;
        $catch = true;

        $this->kernel->expects(self::once())
            ->method('handle')
            ->with(self::identicalTo($request), $type, $catch)
            ->willReturn($this->createMock(Response::class));

        $this->kernelDecorator->handle($request, $type, $catch);

        self::assertEquals(
            '/subDir/console',
            $this->container->getParameter('session.storage.options')['cookie_path']
        );
    }

    public function testHandleForApplicationInSubDirInCaseOfSeveralRequests(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getBasePath')
            ->willReturn('/subDir');
        $type = HttpKernelInterface::MAIN_REQUEST;
        $catch = true;

        $this->kernel->expects(self::exactly(2))
            ->method('handle')
            ->with(self::identicalTo($request), $type, $catch)
            ->willReturn($this->createMock(Response::class));

        $this->kernelDecorator->handle($request, $type, $catch);
        $this->kernelDecorator->handle($request, $type, $catch);

        self::assertEquals(
            '/subDir/console',
            $this->container->getParameter('session.storage.options')['cookie_path']
        );
    }
}
