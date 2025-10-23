<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\EventListener\Tests\Unit\SystemConfig;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SyncBundle\EventListener\SystemConfig\WebsocketServerStateSystemConfigUpdateListener;
use Oro\Bundle\SyncBundle\WebsocketServerState\WebsocketServerStateManagerInterface;
use Oro\Bundle\SyncBundle\WebsocketServerState\WebsocketServerStates;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class WebsocketServerStateSystemConfigUpdateListenerTest extends TestCase
{
    private ApplicationState&MockObject $applicationState;
    private WebsocketServerStateManagerInterface&MockObject $stateManager;
    private WebsocketServerStateSystemConfigUpdateListener $listener;

    protected function setUp(): void
    {
        $this->applicationState = $this->createMock(ApplicationState::class);
        $this->stateManager = $this->createMock(WebsocketServerStateManagerInterface::class);
        $this->listener = new WebsocketServerStateSystemConfigUpdateListener(
            $this->applicationState,
            $this->stateManager
        );
    }

    public function testOnConfigUpdateCallsUpdateStateWithSystemConfigStateWhenApplicationIsInstalled(): void
    {
        $changeSet = [
            'oro_test.some_setting' => [
                'old' => 'old_value',
                'new' => 'new_value',
            ],
        ];
        $scope = 'global';
        $scopeId = 0;

        $event = new ConfigUpdateEvent($changeSet, $scope, $scopeId);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $this->stateManager->expects(self::once())
            ->method('updateState')
            ->with(WebsocketServerStates::SYSTEM_CONFIG)
            ->willReturn(new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC')));

        $this->listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateDoesNotCallUpdateStateWhenApplicationIsNotInstalled(): void
    {
        $changeSet = [
            'oro_test.some_setting' => [
                'old' => 'old_value',
                'new' => 'new_value',
            ],
        ];
        $scope = 'global';
        $scopeId = 0;

        $event = new ConfigUpdateEvent($changeSet, $scope, $scopeId);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $this->stateManager->expects(self::never())
            ->method('updateState');

        $this->listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateWorksWithEmptyChangeSet(): void
    {
        $changeSet = [];
        $scope = 'organization';
        $scopeId = 1;

        $event = new ConfigUpdateEvent($changeSet, $scope, $scopeId);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $this->stateManager->expects(self::once())
            ->method('updateState')
            ->with(WebsocketServerStates::SYSTEM_CONFIG)
            ->willReturn(new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC')));

        $this->listener->onConfigUpdate($event);
    }
}
