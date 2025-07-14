<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Oro\Bundle\LoggerBundle\Provider\ErrorLogNotificationRecipientsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ErrorLogNotificationRecipientsProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private ApplicationState&MockObject $applicationState;
    private ErrorLogNotificationRecipientsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->applicationState = $this->createMock(ApplicationState::class);
        $this->provider = new ErrorLogNotificationRecipientsProvider(
            $this->configManager,
            $this->applicationState
        );
        $this->applicationState->expects(self::any())
            ->method('isInstalled')
            ->willReturn(true);
    }

    /**
     * @dataProvider getRecipientsEmailAddressesDataProvider
     *
     * @param string|null $recipients
     * @param array $expected
     */
    public function testGetRecipientsEmailAddresses(string|null $recipients, array $expected): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getFullConfigKey(Configuration::EMAIL_NOTIFICATION_RECIPIENTS))
            ->willReturn($recipients);

        self::assertEquals($expected, $this->provider->getRecipientsEmailAddresses());
    }

    public function testThatEmailListIsEmptyWhenApplicationIsNotInstalled(): void
    {
        $this->applicationState->expects(self::any())
            ->method('isInstalled')
            ->willReturn(false);

        self::assertEquals([], $this->provider->getRecipientsEmailAddresses());
    }

    public function getRecipientsEmailAddressesDataProvider(): array
    {
        return [
            'no recipients' => [
                'recipients' => null,
                'expected' => [],
            ],
            'empty recipients' => [
                'recipients' => '',
                'expected' => [],
            ],
            '1 recipient' => [
                'recipients' => 'to1@example.org',
                'expected' => ['to1@example.org'],
            ],
            'multiple recipients' => [
                'recipients' => 'to1@example.org;to2@example.org;',
                'expected' => ['to1@example.org', 'to2@example.org'],
            ],
            'recipients with spaces' => [
                'recipients' => ' to1@example.org  ;  to2@example.org  ;  ',
                'expected' => ['to1@example.org', 'to2@example.org'],
            ],
        ];
    }
}
