<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\WebhookRequestProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor\ThinPayloadWebhookRequestProcessor;
use Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor\WebhookRequestContext;
use PHPUnit\Framework\TestCase;

class ThinPayloadWebhookRequestProcessorTest extends TestCase
{
    private ThinPayloadWebhookRequestProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->processor = new ThinPayloadWebhookRequestProcessor();
    }

    public function testProcessRemovesIncludedAttributesAndRelationships(): void
    {
        $payload = [
            'topic' => 'order.created',
            'eventData' => [
                'data' => [
                    'id' => '1',
                    'type' => 'orders',
                    'attributes' => ['status' => 'open', 'total' => 100.0],
                    'relationships' => ['owner' => ['data' => ['type' => 'users', 'id' => '1']]],
                ],
                'included' => [
                    ['type' => 'users', 'id' => '1', 'attributes' => ['username' => 'admin']],
                ],
            ],
        ];

        $context = $this->createContext($payload);
        $this->processor->process($context, $this->createMock(WebhookProducerSettings::class), 'msg-001');

        $result = $context->getPayload();
        self::assertArrayNotHasKey('included', $result['eventData'], '"included" must be removed');
        self::assertArrayNotHasKey('attributes', $result['eventData']['data'], '"attributes" must be removed');
        self::assertArrayNotHasKey('relationships', $result['eventData']['data'], '"relationships" must be removed');
        self::assertEquals('1', $result['eventData']['data']['id']);
        self::assertEquals('orders', $result['eventData']['data']['type']);
    }

    public function testProcessDoesNotModifyUnrelatedPayloadContent(): void
    {
        $payload = [
            'topic' => 'order.created',
            'timestamp' => 1234567890,
            'messageId' => 'msg-002',
            'eventData' => [
                'data' => ['id' => '2', 'type' => 'orders'],
            ],
        ];

        $context = $this->createContext($payload);
        $this->processor->process($context, $this->createMock(WebhookProducerSettings::class), 'msg-002');

        $result = $context->getPayload();
        self::assertEquals('order.created', $result['topic']);
        self::assertEquals(1234567890, $result['timestamp']);
        self::assertEquals('msg-002', $result['messageId']);
        self::assertEquals(['id' => '2', 'type' => 'orders'], $result['eventData']['data']);
    }

    public function testProcessDoesNotModifyOtherContextFields(): void
    {
        $payload = ['eventData' => ['data' => [], 'included' => ['foo']]];
        $context = $this->createContext($payload);
        $context->setHttpMethod('PUT');
        $context->setHeaders(['X-Custom' => 'value']);
        $context->setMetadata(['key' => 'val']);

        $this->processor->process($context, $this->createMock(WebhookProducerSettings::class), 'msg-004');

        self::assertEquals('PUT', $context->getHttpMethod());
        self::assertEquals(['X-Custom' => 'value'], $context->getHeaders());
        self::assertEquals(['key' => 'val'], $context->getMetadata());
    }

    private function createContext(array $payload): WebhookRequestContext
    {
        return new WebhookRequestContext($payload, 'POST', [], [], []);
    }
}
