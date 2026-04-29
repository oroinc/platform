<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\IntegrationBundle\Async\Topic\ProcessSingleWebhookNotificationTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProcessSingleWebhookNotificationTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new ProcessSingleWebhookNotificationTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'full set of options' => [
                'body' => [
                    'webhook_id' => 'webhook_456',
                    'event_data' => ['id' => 2, 'name' => 'Updated Entity', 'status' => 'active'],
                    'timestamp' => 1234567890,
                    'job_id' => 42,
                    'message_id' => 'test-integrity-id-1',
                    'metadata' => ['key' => 'value'],
                ],
                'expectedBody' => [
                    'webhook_id' => 'webhook_456',
                    'event_data' => ['id' => 2, 'name' => 'Updated Entity', 'status' => 'active'],
                    'timestamp' => 1234567890,
                    'job_id' => 42,
                    'message_id' => 'test-integrity-id-1',
                    'metadata' => ['key' => 'value'],
                ],
            ],
            'empty event_data array' => [
                'body' => [
                    'webhook_id' => 'webhook_empty',
                    'event_data' => [],
                    'timestamp' => 1234567800,
                    'message_id' => 'test-integrity-id-2',
                ],
                'expectedBody' => [
                    'webhook_id' => 'webhook_empty',
                    'event_data' => [],
                    'timestamp' => 1234567800,
                    'job_id' => null,
                    'message_id' => 'test-integrity-id-2',
                    'metadata' => [],
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
                'exceptionMessage' => '/The required options "event_data", "message_id", "webhook_id" are missing\./',
            ],
            'missing webhook_id' => [
                'body' => [
                    'event_data' => [],
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "webhook_id" is missing\./',
            ],
            'missing event_data' => [
                'body' => [
                    'webhook_id' => 'webhook_123',
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "event_data" is missing\./',
            ],
            'missing message_id' => [
                'body' => [
                    'webhook_id' => 'webhook_123',
                    'event_data' => [],
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "message_id" is missing\./',
            ],
            'wrong webhook_id type - integer' => [
                'body' => [
                    'webhook_id' => 123,
                    'event_data' => [],
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "webhook_id" with value 123 is expected to be of type "string", '
                    . 'but is of type "int"\./',
            ],
            'wrong webhook_id type - null' => [
                'body' => [
                    'webhook_id' => null,
                    'event_data' => [],
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "webhook_id" with value null is expected to be of type "string", '
                    . 'but is of type "null"\./',
            ],
            'wrong event_data type - string' => [
                'body' => [
                    'webhook_id' => 'webhook_123',
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
                    'webhook_id' => 'webhook_123',
                    'event_data' => 123,
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "event_data" with value 123 is expected to be of type "array", '
                    . 'but is of type "int"\./',
            ],
            'wrong event_data type - null' => [
                'body' => [
                    'webhook_id' => 'webhook_123',
                    'event_data' => null,
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "event_data" with value null is expected to be of type "array", '
                    . 'but is of type "null"\./',
            ],
            'wrong timestamp type - string' => [
                'body' => [
                    'webhook_id' => 'webhook_123',
                    'event_data' => [],
                    'timestamp' => 'not an integer',
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "timestamp" with value "not an integer" is expected to be of type "int", '
                    . 'but is of type "string"\./',
            ],
            'wrong jobId type - string' => [
                'body' => [
                    'webhook_id' => 'webhook_123',
                    'event_data' => [],
                    'job_id' => 'not an integer',
                    'message_id' => 'test-id',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "job_id" with value "not an integer" is expected to be of type "int" or "null", '
                    . 'but is of type "string"\./',
            ],
            'wrong message_id type - integer' => [
                'body' => [
                    'webhook_id' => 'webhook_123',
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
                    'webhook_id' => 'webhook_123',
                    'event_data' => [],
                    'message_id' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "message_id" with value null is expected to be of type "string", '
                    . 'but is of type "null"\./',
            ],
            'wrong metadata type - null' => [
                'body' => [
                    'webhook_id' => 'webhook_123',
                    'event_data' => [],
                    'message_id' => 'abc',
                    'metadata' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "metadata" with value null is expected to be of type "array", but is of type "null"./',
            ],
        ];
    }

    public function testGetName(): void
    {
        self::assertSame(
            'oro_integration.process_single_webhook_notification',
            $this->getTopic()::getName()
        );
    }

    public function testGetDescription(): void
    {
        self::assertSame(
            'Process single webhook notification endpoint',
            $this->getTopic()::getDescription()
        );
    }

    public function testDefaultTimestampIsApplied(): void
    {
        $beforeTime = time();

        $body = [
            'webhook_id' => 'webhook_123',
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

    public function testDefaultJobIdIsNull(): void
    {
        $body = [
            'webhook_id' => 'webhook_123',
            'event_data' => ['id' => 1],
            'timestamp' => 1234567890,
            'message_id' => 'test-integrity-id',
        ];

        $optionsResolver = new OptionsResolver();
        $this->getTopic()->configureMessageBody($optionsResolver);
        $resolved = $optionsResolver->resolve($body);

        self::assertArrayHasKey('job_id', $resolved);
        self::assertNull($resolved['job_id']);
    }
}
