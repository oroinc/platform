<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Oro\Bundle\LoggerBundle\Provider\ErrorLogNotificationRecipientsProvider;

class ErrorLogNotificationRecipientsProviderTest extends \PHPUnit\Framework\TestCase
{
    private ConfigManager&\PHPUnit\Framework\MockObject\MockObject $configManager;

    private ApplicationState&\PHPUnit\Framework\MockObject\MockObject $applicationState;

    private ErrorLogNotificationRecipientsProvider $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->applicationState = $this->createMock(ApplicationState::class);
        $this->provider = new ErrorLogNotificationRecipientsProvider(
            $this->configManager,
            $this->applicationState
        );
        $this->applicationState->method('isInstalled')->willReturn(true);
    }

    /**
     * @dataProvider getRecipientsEmailAddressesDataProvider
     *
     * @param string|null $recipients
     * @param array $expected
     */
    public function testGetRecipientsEmailAddresses(string|null $recipients, array $expected): void
    {
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(Configuration::getFullConfigKey(Configuration::EMAIL_NOTIFICATION_RECIPIENTS))
            ->willReturn($recipients);

        self::assertEquals($expected, $this->provider->getRecipientsEmailAddresses());
    }

    public function testThatEmailListIsEmptyWhenApplicationIsNotInstalled()
    {
        $this->applicationState->method('isInstalled')->willReturn(false);

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
