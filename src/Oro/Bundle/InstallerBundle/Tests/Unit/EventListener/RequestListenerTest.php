<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\InstallerBundle\EventListener\RequestListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RequestListenerTest extends TestCase
{
    private RequestListener $listener;

    private ApplicationState|MockObject $applicationState;

    private Request|MockObject $request;

    private string $templatePath = __DIR__ . '/data/notinstalled.html';

    #[\Override]
    protected function setUp(): void
    {
        $this->applicationState = $this->createMock(ApplicationState::class);
        $this->request = $this->createMock(Request::class);
        $this->listener = new RequestListener($this->applicationState);
        $this->listener->setTemplatePath($this->templatePath);
    }

    public function testApplicationIsNotInstalled()
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $this->listener->onRequest($event);
        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(503, $response->getStatusCode());
        self::assertEquals('Application not installed', trim($response->getContent()));
    }

    public function testApplicationIsInstalled()
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $this->listener->onRequest($event);
        $response = $event->getResponse();

        self::assertNull($response);
    }
}
