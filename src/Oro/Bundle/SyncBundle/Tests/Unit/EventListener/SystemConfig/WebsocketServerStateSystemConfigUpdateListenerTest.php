<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener\SystemConfig;

use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\TableNotFoundException;
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

    #[\Override]
    protected function setUp(): void
    {
        $this->applicationState = $this->createMock(ApplicationState::class);
        $this->stateManager = $this->createMock(WebsocketServerStateManagerInterface::class);
    }

    public function testOnConfigUpdateUpdatesStateWhenWatchedOptionChanged(): void
    {
        $listener = new WebsocketServerStateSystemConfigUpdateListener(
            $this->applicationState,
            $this->stateManager,
            ['oro_ui.application_url']
        );
        $event = new ConfigUpdateEvent(
            ['oro_ui.application_url' => ['old' => 'http://old.example.com', 'new' => 'http://new.example.com']],
            'global',
            0
        );

        $this->applicationState->method('isInstalled')
            ->willReturn(true);
        $this->stateManager->expects(self::once())
            ->method('updateState')
            ->with(WebsocketServerStates::SYSTEM_CONFIG)
            ->willReturn(new \DateTime());

        $listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateUpdatesStateWhenOneOfSeveralWatchedOptionsChanged(): void
    {
        $listener = new WebsocketServerStateSystemConfigUpdateListener(
            $this->applicationState,
            $this->stateManager,
            ['oro_test.first_option', 'oro_test.second_option']
        );
        $event = new ConfigUpdateEvent(
            ['oro_test.second_option' => ['old' => 'a', 'new' => 'b']],
            'global',
            0
        );

        $this->applicationState->method('isInstalled')
            ->willReturn(true);
        $this->stateManager->expects(self::once())
            ->method('updateState')
            ->with(WebsocketServerStates::SYSTEM_CONFIG)
            ->willReturn(new \DateTime());

        $listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateUpdatesStateOnceWhenSeveralWatchedOptionsChanged(): void
    {
        $listener = new WebsocketServerStateSystemConfigUpdateListener(
            $this->applicationState,
            $this->stateManager,
            ['oro_test.first_option', 'oro_test.second_option']
        );
        $event = new ConfigUpdateEvent(
            [
                'oro_test.first_option' => ['old' => 'a', 'new' => 'b'],
                'oro_test.second_option' => ['old' => 'c', 'new' => 'd'],
            ],
            'global',
            0
        );

        $this->applicationState->method('isInstalled')
            ->willReturn(true);
        $this->stateManager->expects(self::once())
            ->method('updateState')
            ->with(WebsocketServerStates::SYSTEM_CONFIG)
            ->willReturn(new \DateTime());

        $listener->onConfigUpdate($event);
    }

    /**
     * @dataProvider scopeDataProvider
     */
    public function testOnConfigUpdateUpdatesStateForWatchedOptionInAnyScope(
        string $scope,
        int $scopeId
    ): void {
        $listener = new WebsocketServerStateSystemConfigUpdateListener(
            $this->applicationState,
            $this->stateManager,
            ['oro_test.watched_option']
        );
        $event = new ConfigUpdateEvent(
            ['oro_test.watched_option' => ['old' => 'a', 'new' => 'b']],
            $scope,
            $scopeId
        );

        $this->applicationState->method('isInstalled')
            ->willReturn(true);
        $this->stateManager->expects(self::once())
            ->method('updateState')
            ->with(WebsocketServerStates::SYSTEM_CONFIG)
            ->willReturn(new \DateTime());

        $listener->onConfigUpdate($event);
    }

    public static function scopeDataProvider(): array
    {
        return [
            'global scope' => ['global', 0],
            'organization scope' => ['organization', 1],
            'website scope' => ['website', 5],
        ];
    }

    public function testOnConfigUpdateDoesNotUpdateStateWhenOnlyOtherOptionChanged(): void
    {
        $listener = new WebsocketServerStateSystemConfigUpdateListener(
            $this->applicationState,
            $this->stateManager,
            ['oro_ui.application_url']
        );
        $event = new ConfigUpdateEvent(
            ['oro_email.attachment_preview_limit' => ['old' => 8, 'new' => 9]],
            'global',
            0
        );

        $this->applicationState->method('isInstalled')
            ->willReturn(true);
        $this->stateManager->expects(self::never())
            ->method('updateState');

        $listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateDoesNotUpdateStateWhenChangeSetIsEmpty(): void
    {
        $listener = new WebsocketServerStateSystemConfigUpdateListener(
            $this->applicationState,
            $this->stateManager,
            ['oro_ui.application_url']
        );
        $event = new ConfigUpdateEvent([], 'global', 0);

        $this->applicationState->method('isInstalled')
            ->willReturn(true);
        $this->stateManager->expects(self::never())
            ->method('updateState');

        $listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateDoesNotUpdateStateWhenNoOptionsAreWatched(): void
    {
        $listener = new WebsocketServerStateSystemConfigUpdateListener(
            $this->applicationState,
            $this->stateManager,
            []
        );
        $event = new ConfigUpdateEvent(
            ['oro_ui.application_url' => ['old' => 'http://old.example.com', 'new' => 'http://new.example.com']],
            'global',
            0
        );

        $this->applicationState->method('isInstalled')
            ->willReturn(true);
        $this->stateManager->expects(self::never())
            ->method('updateState');

        $listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateDoesNotUpdateStateWhenWatchedOptionOnlyFallsBackToParentScope(): void
    {
        $listener = new WebsocketServerStateSystemConfigUpdateListener(
            $this->applicationState,
            $this->stateManager,
            ['oro_test.watched_option']
        );
        $event = new ConfigUpdateEvent(
            [],
            'website',
            5,
            ['oro_test.watched_option' => ['old' => 'a', 'new' => 'a', 'action' => 'add']]
        );

        $this->applicationState->method('isInstalled')
            ->willReturn(true);
        $this->stateManager->expects(self::never())
            ->method('updateState');

        $listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateDoesNotUpdateStateWhenApplicationIsNotInstalled(): void
    {
        $listener = new WebsocketServerStateSystemConfigUpdateListener(
            $this->applicationState,
            $this->stateManager,
            ['oro_ui.application_url']
        );
        $event = new ConfigUpdateEvent(
            ['oro_ui.application_url' => ['old' => 'http://old.example.com', 'new' => 'http://new.example.com']],
            'global',
            0
        );

        $this->applicationState->method('isInstalled')
            ->willReturn(false);
        $this->stateManager->expects(self::never())
            ->method('updateState');

        $listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateIgnoresMissingStateTable(): void
    {
        $listener = new WebsocketServerStateSystemConfigUpdateListener(
            $this->applicationState,
            $this->stateManager,
            ['oro_ui.application_url']
        );
        $event = new ConfigUpdateEvent(
            ['oro_ui.application_url' => ['old' => 'http://old.example.com', 'new' => 'http://new.example.com']],
            'global',
            0
        );

        $this->applicationState->method('isInstalled')
            ->willReturn(true);
        $this->stateManager->expects(self::once())
            ->method('updateState')
            ->with(WebsocketServerStates::SYSTEM_CONFIG)
            ->willThrowException(new TableNotFoundException($this->createMock(DriverException::class), null));

        $listener->onConfigUpdate($event);
    }
}
