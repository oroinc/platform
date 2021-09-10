<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Request;

use Oro\Bundle\SecurityBundle\Request\SessionHttpKernelDecorator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SessionHttpKernelDecoratorTest extends \PHPUnit\Framework\TestCase
{
    private const SESSION_OPTIONS = [
        'name'            => 'CONSOLE',
        'cookie_path'     => '/console',
        'cookie_lifetime' => 10
    ];

    /** @var HttpKernel|\PHPUnit\Framework\MockObject\MockObject */
    private $kernel;

    /** @var ContainerInterface */
    private $container;

    /** @var SessionHttpKernelDecorator */
    private $kernelDecorator;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(HttpKernel::class);
        $this->container = new ContainerStub([
            'session.storage.options' => self::SESSION_OPTIONS
        ]);

        $this->kernelDecorator = new SessionHttpKernelDecorator($this->kernel, $this->container);
    }

    public function testTerminate()
    {
        $request = Request::create('http://localhost/admin/test.php');
        $response = $this->createMock(Response::class);

        $this->kernel->expects(self::once())
            ->method('terminate')
            ->with(self::identicalTo($request), self::identicalTo($response));

        $this->kernelDecorator->terminate($request, $response);
    }

    public function testHandleForApplicationInRootDir()
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getBasePath')
            ->willReturn('/');
        $type = HttpKernelInterface::MASTER_REQUEST;
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

    public function testHandleForApplicationInSubDir()
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getBasePath')
            ->willReturn('/subDir');
        $type = HttpKernelInterface::MASTER_REQUEST;
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

    public function testHandleForApplicationInSubDirInCaseOfSeveralRequests()
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getBasePath')
            ->willReturn('/subDir');
        $type = HttpKernelInterface::MASTER_REQUEST;
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
