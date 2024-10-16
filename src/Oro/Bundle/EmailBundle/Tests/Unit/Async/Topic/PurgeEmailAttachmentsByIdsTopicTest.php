<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsByIdsTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class PurgeEmailAttachmentsByIdsTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new PurgeEmailAttachmentsByIdsTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'jobId' => 42,
                    'ids' => [142, 1142],
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'ids' => [142, 1142],
                ],
            ],
            'all options' => [
                'body' => [
                    'jobId' => 42,
                    'ids' => [142, 1142],
                    'size' => 101,
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'ids' => [142, 1142],
                    'size' => 101,
                ],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "ids", "jobId" are missing./',
            ],
        ];
    }
}
