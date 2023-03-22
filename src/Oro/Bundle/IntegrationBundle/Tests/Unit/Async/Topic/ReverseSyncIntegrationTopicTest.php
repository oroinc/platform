<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\IntegrationBundle\Async\Topic\ReverseSyncIntegrationTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ReverseSyncIntegrationTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ReverseSyncIntegrationTopic();
    }

    public function validBodyDataProvider(): array
    {
        $fullOptionsSet = [
            'integration_id' => 1,
            'connector' => 'bar',
            'connector_parameters' => [
                'foo' => 'bar',
            ],
        ];

        return [
            'only required options' => [
                'body' => [
                    'integration_id' => 1,
                    'connector' => 'bar',
                ],
                'expectedBody' => [
                    'integration_id' => 1,
                    'connector' => 'bar',
                    'connector_parameters' => [],
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
                    '/The required options "connector", "integration_id" are missing./',
            ],
            'wrong integration_id type' => [
                'body' => [
                    'integration_id' => null,
                    'connector' => 'bar',
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
                    'connector' => 'bar',
                    'connector_parameters' => 1,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "connector_parameters" with value 1 is expected to be of type "array", '
                    . 'but is of type "int"./',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro_integration:revers_sync_integration:1',
            $this->getTopic()->createJobName(['integration_id' => 1])
        );
    }
}
