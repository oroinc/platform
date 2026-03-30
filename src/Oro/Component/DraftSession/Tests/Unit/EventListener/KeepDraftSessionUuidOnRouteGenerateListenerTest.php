<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\Event\RouterGenerateEvent;
use Oro\Component\DraftSession\EventListener\KeepDraftSessionUuidOnRouteGenerateListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;

final class KeepDraftSessionUuidOnRouteGenerateListenerTest extends TestCase
{
    private RequestContextAwareInterface&MockObject $router;
    private RequestContext $requestContext;
    private KeepDraftSessionUuidOnRouteGenerateListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->router = $this->createMock(RequestContextAwareInterface::class);
        $this->requestContext = new RequestContext();

        $this->router
            ->method('getContext')
            ->willReturn($this->requestContext);

        $this->listener = new KeepDraftSessionUuidOnRouteGenerateListener(
            $this->router,
            'draftSessionUuid',
            ['oro_order_create', 'oro_order_update', 'oro_order_view']
        );
    }

    public function testDoesNothingWhenRouteNotApplicable(): void
    {
        $event = new RouterGenerateEvent('some_other_route', [], 1);

        $this->listener->onRouterGenerate($event);

        self::assertNull($event->getParameter('draftSessionUuid'));
    }

    public function testDoesNothingWhenParameterAlreadySet(): void
    {
        $existingUuid = 'existing-uuid-123';
        $event = new RouterGenerateEvent('oro_order_create', ['draftSessionUuid' => $existingUuid], 1);

        $this->listener->onRouterGenerate($event);

        self::assertEquals($existingUuid, $event->getParameter('draftSessionUuid'));
    }

    public function testDoesNothingWhenUuidNotInRequestContext(): void
    {
        $event = new RouterGenerateEvent('oro_order_create', [], 1);

        $this->listener->onRouterGenerate($event);

        self::assertNull($event->getParameter('draftSessionUuid'));
    }

    public function testSetsUuidFromRequestContext(): void
    {
        $contextUuid = 'context-uuid-456';
        $this->requestContext->setParameter('draftSessionUuid', $contextUuid);

        $event = new RouterGenerateEvent('oro_order_create', [], 1);

        $this->listener->onRouterGenerate($event);

        self::assertEquals($contextUuid, $event->getParameter('draftSessionUuid'));
    }

    public function testWorksWithMultipleApplicableRoutes(): void
    {
        $contextUuid = 'multi-route-uuid';
        $this->requestContext->setParameter('draftSessionUuid', $contextUuid);

        $event1 = new RouterGenerateEvent('oro_order_create', [], 1);
        $this->listener->onRouterGenerate($event1);
        self::assertEquals($contextUuid, $event1->getParameter('draftSessionUuid'));

        $event2 = new RouterGenerateEvent('oro_order_update', [], 1);
        $this->listener->onRouterGenerate($event2);
        self::assertEquals($contextUuid, $event2->getParameter('draftSessionUuid'));

        $event3 = new RouterGenerateEvent('oro_order_view', [], 1);
        $this->listener->onRouterGenerate($event3);
        self::assertEquals($contextUuid, $event3->getParameter('draftSessionUuid'));
    }

    public function testDoesNothingWithEmptyApplicableRoutes(): void
    {
        $listener = new KeepDraftSessionUuidOnRouteGenerateListener(
            $this->router,
            'draftSessionUuid',
            []
        );

        $this->requestContext->setParameter('draftSessionUuid', 'some-uuid');

        $event = new RouterGenerateEvent('oro_order_create', [], 1);
        $listener->onRouterGenerate($event);

        self::assertNull($event->getParameter('draftSessionUuid'));
    }

    public function testCustomParameterName(): void
    {
        $listener = new KeepDraftSessionUuidOnRouteGenerateListener(
            $this->router,
            'customUuid',
            ['custom_route']
        );

        $customUuid = 'custom-uuid-789';
        $this->requestContext->setParameter('customUuid', $customUuid);

        $event = new RouterGenerateEvent('custom_route', [], 1);
        $listener->onRouterGenerate($event);

        self::assertEquals($customUuid, $event->getParameter('customUuid'));
    }

    public function testDoesNothingWhenRequestContextParameterIsNull(): void
    {
        $this->requestContext->setParameter('draftSessionUuid', null);

        $event = new RouterGenerateEvent('oro_order_create', [], 1);

        $this->listener->onRouterGenerate($event);

        self::assertNull($event->getParameter('draftSessionUuid'));
    }

    public function testDoesNothingWhenRequestContextParameterIsEmptyString(): void
    {
        $this->requestContext->setParameter('draftSessionUuid', '');

        $event = new RouterGenerateEvent('oro_order_create', [], 1);

        $this->listener->onRouterGenerate($event);

        self::assertNull($event->getParameter('draftSessionUuid'));
    }

    public function testDoesNotOverrideExistingParameterEvenWithContextValue(): void
    {
        $existingUuid = 'existing-uuid';
        $contextUuid = 'context-uuid';

        $this->requestContext->setParameter('draftSessionUuid', $contextUuid);

        $event = new RouterGenerateEvent('oro_order_create', ['draftSessionUuid' => $existingUuid], 1);

        $this->listener->onRouterGenerate($event);

        self::assertEquals($existingUuid, $event->getParameter('draftSessionUuid'));
    }
}
