<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\IntegrationBundle\Async\Topic\SyncIntegrationTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SyncIntegrationTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new SyncIntegrationTopic();
    }

    public function validBodyDataProvider(): array
    {
        $fullOptionsSet = [
            'integration_id' => 1,
            'connector' => 'bar',
            'connector_parameters' => [
                'foo' => 'bar',
            ],
            'transport_batch_size' => 200,
        ];

        return [
            'only required options' => [
                'body' => [
                    'integration_id' => 1,
                ],
                'expectedBody' => [
                    'integration_id' => 1,
                    'connector' => null,
                    'connector_parameters' => [],
                    'transport_batch_size' => 100,
                ],
            ],
            'full set of options' => [
                'body' => $fullOptionsSet,
                'expectedBody' => $fullOptionsSet,
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required option "integration_id" is missing./',
            ],
            'wrong integration_id type' => [
                'body' => [
                    'integration_id' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "integration_id" with value null is expected to be of type "string" or "int", '
                    . 'but is of type "null"./',
            ],
            'wrong connector type' => [
                'body' => [
                    'integration_id' => 1,
                    'connector' => [],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "connector" with value array is expected to be of type "null" or "string", '
                    . 'but is of type "array"./',
            ],
            'wrong connector_parameters type' => [
                'body' => [
                    'integration_id' => 1,
                    'connector_parameters' => 1,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "connector_parameters" with value 1 is expected to be of type "array", '
                    . 'but is of type "int"./',
            ],
            'wrong transport_batch_size type' => [
                'body' => [
                    'integration_id' => 1,
                    'transport_batch_size' => [],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "transport_batch_size" with value array is expected to be of type "int", '
                    . 'but is of type "array"./',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro_integration:sync_integration:1',
            $this->getTopic()->createJobName(['integration_id' => 1])
        );
    }
}
