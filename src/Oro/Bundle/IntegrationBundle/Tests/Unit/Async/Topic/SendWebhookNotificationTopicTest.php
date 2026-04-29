<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\IntegrationBundle\Async\Topic\SendWebhookNotificationTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SendWebhookNotificationTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new SendWebhookNotificationTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'full set of options' => [
                'body' => [
                    'topic' => 'product.created',
                    'event_data' => ['id' => 1, 'name' => 'Test'],
                    'timestamp' => 1234567890,
                    'entity_class' => 'App\Entity\Product',
                    'entity_id' => 42,
                    'message_id' => 'test-integrity-id-1',
                ],
                'expectedBody' => [
                    'topic' => 'product.created',
                    'event_data' => ['id' => 1, 'name' => 'Test'],
                    'timestamp' => 1234567890,
                    'entity_class' => 'App\Entity\Product',
                    'entity_id' => 42,
                    'message_id' => 'test-integrity-id-1',
                ],
            ],
            'with entity_class only' => [
                'body' => [
                    'topic' => 'order.created',
                    'event_data' => [],
                    'timestamp' => 1234567700,
                    'entity_class' => 'App\Entity\Order',
                    'message_id' => 'test-integrity-id-2',
                ],
                'expectedBody' => [
                    'topic' => 'order.created',
                    'event_data' => [],
                    'timestamp' => 1234567700,
                    'entity_class' => 'App\Entity\Order',
                    'message_id' => 'test-integrity-id-2',
                ],
            ],
            'with string entity_id' => [
                'body' => [
                    'topic' => 'product.updated',
                    'event_data' => ['uuid' => 'abc-123'],
                    'timestamp' => 1234567600,
                    'entity_id' => 'uuid-string',
                    'message_id' => 'test-integrity-id-3',
                ],
                'expectedBody' => [
                    'topic' => 'product.updated',
                    'event_data' => ['uuid' => 'abc-123'],
                    'timestamp' => 1234567600,
                    'entity_id' => 'uuid-string',
                    'message_id' => 'test-integrity-id-3',
                ],
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty body' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "event_data", "message_id", "topic" are missing\./',
            ],
            'missing topic' => [
                'body' => [
                    'event_data' => [],
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "topic" is missing\./',
            ],
            'missing event_data' => [
                'body' => [
                    'topic' => 'product.created',
                    'message_id' => 'test-id'
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "event_data" is missing\./',
            ],
            'missing message_id' => [
                'body' => [
                    'topic' => 'product.created',
                    'event_data' => []
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "message_id" is missing\./',
            ],
            'wrong topic type - integer' => [
                'body' => [
                    'topic' => 123,
                    'event_data' => [],
                    'message_id' => 'test-id'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "topic" with value 123 is expected to be of type "string", '
                    . 'but is of type "int"\./',
            ],
            'wrong topic type - null' => [
                'body' => [
                    'topic' => null,
                    'event_data' => [],
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "topic" with value null is expected to be of type "string", '
                    . 'but is of type "null"\./',
            ],
            'wrong event_data type - string' => [
                'body' => [
                    'topic' => 'product.created',
                    'event_data' => 'not an array',
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "event_data" with value "not an array" is expected to be of type "array", '
                    . 'but is of type "string"\./',
            ],
            'wrong event_data type - integer' => [
                'body' => [
                    'topic' => 'product.created',
                    'event_data' => 789,
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "event_data" with value 789 is expected to be of type "array", '
                    . 'but is of type "int"\./',
            ],
            'wrong timestamp type - string' => [
                'body' => [
                    'topic' => 'product.created',
                    'event_data' => [],
                    'timestamp' => 'not an int',
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "timestamp" with value "not an int" is expected to be of type "int", '
                    . 'but is of type "string"\./',
            ],
            'wrong entity_class type - integer' => [
                'body' => [
                    'topic' => 'product.created',
                    'event_data' => [],
                    'entity_class' => 123,
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "entity_class" with value 123 is expected to be of type "string" or "null", '
                    . 'but is of type "int"\./',
            ],
            'wrong entity_class type - array' => [
                'body' => [
                    'topic' => 'product.created',
                    'event_data' => [],
                    'entity_class' => [],
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "entity_class" with value array is expected to be of type "string" or "null", '
                    . 'but is of type "array"\./',
            ],
            'wrong entity_id type - array' => [
                'body' => [
                    'topic' => 'product.created',
                    'event_data' => [],
                    'entity_id' => [],
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "entity_id" with value array is expected to be of type "int" or "string" or "null", '
                    . 'but is of type "array"\./',
            ],
            'wrong message_id type - integer' => [
                'body' => [
                    'topic' => 'product.created',
                    'event_data' => [],
                    'message_id' => 123,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "message_id" with value 123 is expected to be of type "string", '
                    . 'but is of type "int"\./',
            ],
            'wrong message_id type - null' => [
                'body' => [
                    'topic' => 'product.created',
                    'event_data' => [],
                    'message_id' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "message_id" with value null is expected to be of type "string", '
                    . 'but is of type "null"\./',
            ]
        ];
    }

    public function testGetName(): void
    {
        self::assertSame(
            'oro_integration.webhook_notification',
            $this->getTopic()::getName()
        );
    }

    public function testGetDescription(): void
    {
        self::assertSame(
            'Send webhook notification to remote endpoints',
            $this->getTopic()::getDescription()
        );
    }

    public function testDefaultTimestampIsApplied(): void
    {
        $beforeTime = time();

        $body = [
            'topic' => 'product.created',
            'event_data' => ['id' => 1],
            'message_id' => 'test-integrity-id',
        ];

        $optionsResolver = new OptionsResolver();
        $this->getTopic()->configureMessageBody($optionsResolver);
        $resolved = $optionsResolver->resolve($body);

        $afterTime = time();

        self::assertArrayHasKey('timestamp', $resolved);
        self::assertIsInt($resolved['timestamp']);
        self::assertGreaterThanOrEqual($beforeTime, $resolved['timestamp']);
        self::assertLessThanOrEqual($afterTime, $resolved['timestamp']);
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro_integration.webhook_notification:product.updated:test-message-id',
            $this->getTopic()->createJobName([
                'topic' => 'product.updated',
                'message_id' => 'test-message-id',
            ])
        );
    }
}
