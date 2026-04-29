<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Provider\WebhookProducerSettingsEntityNameProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebhookProducerSettingsEntityNameProviderTest extends TestCase
{
    private TranslatorInterface&MockObject $translator;
    private WebhookProducerSettingsEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects(self::any())
            ->method('trans')
            ->with('oro.integration.webhookproducersettings.entity_name.label')
            ->willReturnCallback(function (string $id, array $parameters) {
                return str_replace(
                    ['%topic%', '%notificationUrl%'],
                    [
                        $parameters['%topic%'] ?? '%topic%',
                        $parameters['%notificationUrl%'] ?? '%notificationUrl%'
                    ],
                    'Webhook %topic% - %notificationUrl%'
                );
            });

        $this->provider = new WebhookProducerSettingsEntityNameProvider($this->translator);
    }

    /**
     * @dataProvider getNameDataProvider
     */
    public function testGetName(string $format, ?string $locale, object $entity, string|false $expected): void
    {
        $this->assertEquals($expected, $this->provider->getName($format, $locale, $entity));
    }

    public function getNameDataProvider(): array
    {
        $webhook = new WebhookProducerSettings();
        $webhook->setTopic('order.created');
        $webhook->setNotificationUrl('https://example.com/webhook');

        return [
            'unsupported class' => [
                'format' => '',
                'locale' => null,
                'entity' => new \stdClass(),
                'expected' => false
            ],
            'unsupported format - short' => [
                'format' => EntityNameProviderInterface::SHORT,
                'locale' => null,
                'entity' => $webhook,
                'expected' => false
            ],
            'unsupported format - empty' => [
                'format' => '',
                'locale' => null,
                'entity' => $webhook,
                'expected' => false
            ],
            'correct data' => [
                'format' => EntityNameProviderInterface::FULL,
                'locale' => 'en',
                'entity' => $webhook,
                'expected' => 'Webhook order.created - https://example.com/webhook'
            ]
        ];
    }

    /**
     * @dataProvider getNameDQLDataProvider
     */
    public function testGetNameDQL(
        string $format,
        ?string $locale,
        string $className,
        string $alias,
        string|false $expected
    ): void {
        $this->assertEquals($expected, $this->provider->getNameDQL($format, $locale, $className, $alias));
    }

    public function getNameDQLDataProvider(): array
    {
        return [
            'unsupported class' => [
                'format' => '',
                'locale' => null,
                'className' => '',
                'alias' => 'webhook',
                'expected' => false
            ],
            'unsupported format - short' => [
                'format' => EntityNameProviderInterface::SHORT,
                'locale' => null,
                'className' => WebhookProducerSettings::class,
                'alias' => 'webhook',
                'expected' => false
            ],
            'unsupported format - empty' => [
                'format' => '',
                'locale' => null,
                'className' => WebhookProducerSettings::class,
                'alias' => 'webhook',
                'expected' => false
            ],
            'correct data' => [
                'format' => EntityNameProviderInterface::FULL,
                'locale' => 'en',
                'className' => WebhookProducerSettings::class,
                'alias' => 'webhook',
                'expected' => "CONCAT('Webhook '," .
                    " webhook.topic," .
                    " ' - '," .
                    " webhook.notificationUrl, '')"
            ]
        ];
    }
}
