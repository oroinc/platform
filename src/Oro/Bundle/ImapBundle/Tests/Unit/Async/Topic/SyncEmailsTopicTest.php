<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ImapBundle\Async\Topic\SyncEmailsTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SyncEmailsTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new SyncEmailsTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'ids' => [42, 142],
                ],
                'expectedBody' => [
                    'ids' => [42, 142],
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'ids' => ['42', '142'],
                ],
                'expectedBody' => [
                    'ids' => ['42', '142'],
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "ids" is missing./',
            ],
        ];
    }
}
