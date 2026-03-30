<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Component\DraftSession\EventListener\SetDraftSessionUuidToRequestContextListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;

final class SetDraftSessionUuidToRequestContextListenerTest extends TestCase
{
    private ApplicationState&MockObject $applicationState;
    private RequestContextAwareInterface&MockObject $router;
    private RequestContext $requestContext;
    private SetDraftSessionUuidToRequestContextListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->applicationState = $this->createMock(ApplicationState::class);
        $this->router = $this->createMock(RequestContextAwareInterface::class);
        $this->requestContext = new RequestContext();

        $this->router
            ->method('getContext')
            ->willReturn($this->requestContext);

        $this->listener = new SetDraftSessionUuidToRequestContextListener(
            $this->applicationState,
            $this->router,
            'uuid'
        );
    }

    public function testDoesNothingWhenApplicationNotInstalled(): void
    {
        $this->applicationState
            ->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->onKernelRequest($event);

        self::assertNull($this->requestContext->getParameter('uuid'));
    }

    public function testDoesNothingWhenUuidAlreadyInContext(): void
    {
        $existingUuid = 'existing-uuid-123';
        $this->requestContext->setParameter('uuid', $existingUuid);

        $this->applicationState
            ->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->onKernelRequest($event);

        self::assertEquals($existingUuid, $this->requestContext->getParameter('uuid'));
    }

    public function testSetsUuidFromRequest(): void
    {
        $requestUuid = 'request-uuid-456';

        $this->applicationState
            ->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(['uuid' => $requestUuid]),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->onKernelRequest($event);

        self::assertEquals($requestUuid, $this->requestContext->getParameter('uuid'));
    }

    public function testDoesNothingWhenUuidNotInRequest(): void
    {
        $this->applicationState
            ->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->onKernelRequest($event);

        self::assertNull($this->requestContext->getParameter('uuid'));
    }

    public function testUsesCustomParameterName(): void
    {
        $customListener = new SetDraftSessionUuidToRequestContextListener(
            $this->applicationState,
            $this->router,
            'custom_uuid'
        );

        $this->applicationState
            ->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(['custom_uuid' => 'custom-value']),
            HttpKernelInterface::MAIN_REQUEST
        );

        $customListener->onKernelRequest($event);

        self::assertEquals('custom-value', $this->requestContext->getParameter('custom_uuid'));
    }
}
