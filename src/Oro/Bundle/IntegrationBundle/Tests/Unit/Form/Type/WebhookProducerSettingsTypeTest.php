<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Form\Type\WebhookProducerSettingsType;
use Oro\Bundle\IntegrationBundle\Model\WebhookTopic;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use Oro\Bundle\IntegrationBundle\Provider\WebhookFormatProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebhookProducerSettingsTypeTest extends FormIntegrationTestCase
{
    private WebhookConfigurationProvider&MockObject $webhookConfigurationProvider;
    private WebhookFormatProvider&MockObject $webhookFormatProvider;
    private WebhookProducerSettingsType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->webhookConfigurationProvider = $this->createMock(WebhookConfigurationProvider::class);
        $this->webhookConfigurationProvider->expects(self::any())
            ->method('getAvailableTopics')
            ->willReturn([
                new WebhookTopic('channel1.created', 'Channel1 created'),
                new WebhookTopic('channel1.updated', 'Channel1 updated'),
                new WebhookTopic('channel1.deleted', 'Channel1 deleted'),
                new WebhookTopic('channel2.created', 'Channel1 created'),
                new WebhookTopic('channel2.updated', 'Channel2 updated')
            ]);
        $this->webhookFormatProvider = $this->createMock(WebhookFormatProvider::class);
        $this->webhookFormatProvider->expects(self::any())
            ->method('getFormats')
            ->willReturn(['json' => 'JSON', 'xml' => 'XML']);

        $this->formType = new WebhookProducerSettingsType(
            $this->webhookConfigurationProvider,
            $this->webhookFormatProvider
        );
        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType, new Select2ChoiceType()], [])
        ];
    }

    public function testBuildFormHasExpectedFields(): void
    {
        $form = $this->factory->create(WebhookProducerSettingsType::class);

        self::assertTrue($form->has('enabled'));
        self::assertTrue($form->has('notificationUrl'));
        self::assertTrue($form->has('secret'));
        self::assertTrue($form->has('topic'));
        self::assertTrue($form->has('verifySsl'));
        self::assertTrue($form->has('format'));
        self::assertFalse($form->has('system'));
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        self::assertEquals(WebhookProducerSettings::class, $resolver->resolve()['data_class']);
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_integration_webhook_producer_settings', $this->formType->getBlockPrefix());
    }

    public function testTopicChoicesAreBuiltFromProvider(): void
    {
        $form = $this->factory->create(WebhookProducerSettingsType::class);
        $view = $form->createView();

        $choiceValues = array_map(
            static fn ($choiceView) => $choiceView->value,
            $view['topic']->vars['choices']
        );

        self::assertContains('channel1.created', $choiceValues);
        self::assertContains('channel2.updated', $choiceValues);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        ?WebhookProducerSettings $defaultData,
        array $submittedData,
        WebhookProducerSettings $expectedData
    ): void {
        $form = $this->factory->create(WebhookProducerSettingsType::class, $defaultData);
        $form->submit($submittedData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        $actualData = $form->getData();
        self::assertInstanceOf(WebhookProducerSettings::class, $actualData);
        self::assertEquals($expectedData->isEnabled(), $actualData->isEnabled());
        self::assertEquals($expectedData->getNotificationUrl(), $actualData->getNotificationUrl());
        self::assertEquals($expectedData->getSecret(), $actualData->getSecret());
        self::assertEquals($expectedData->getTopic(), $actualData->getTopic());
        self::assertEquals($expectedData->isVerifySsl(), $actualData->isVerifySsl());
        self::assertEquals($expectedData->getFormat(), $actualData->getFormat());
    }

    public static function submitDataProvider(): array
    {
        $webhook1 = new WebhookProducerSettings();
        $webhook1->setEnabled(true);
        $webhook1->setNotificationUrl('https://example.com/webhook');
        $webhook1->setSecret('secret123');
        $webhook1->setTopic('channel1.created');
        $webhook1->setVerifySsl(true);
        $webhook1->setFormat('json');

        $webhook2 = new WebhookProducerSettings();
        $webhook2->setEnabled(false);
        $webhook2->setNotificationUrl('https://test.example.com/hook');
        $webhook2->setSecret('');
        $webhook2->setTopic('channel2.updated');
        $webhook2->setVerifySsl(false);
        $webhook2->setFormat('xml');

        return [
            'new webhook' => [
                'defaultData' => null,
                'submittedData' => [
                    'enabled' => true,
                    'notificationUrl' => 'https://example.com/webhook',
                    'secret' => 'secret123',
                    'topic' => 'channel1.created',
                    'verifySsl' => true,
                    'format' => 'json'
                ],
                'expectedData' => $webhook1,
            ],
            'update existing webhook' => [
                'defaultData' => $webhook1,
                'submittedData' => [
                    'notificationUrl' => 'https://test.example.com/hook',
                    'secret' => '',
                    'topic' => 'channel2.updated',
                    'verifySsl' => false,
                    'format' => 'xml',
                ],
                'expectedData' => $webhook2,
            ],
        ];
    }
}
