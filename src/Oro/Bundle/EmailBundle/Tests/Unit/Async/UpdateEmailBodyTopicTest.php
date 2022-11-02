<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailBodyTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class UpdateEmailBodyTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new UpdateEmailBodyTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'expectedBody' => [],
            ],
            'pageNumber' => [
                'body' => [
                    'pageNumber' => 12,
                ],
                'expectedBody' => [
                    'pageNumber' => 12,
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'invalid option' => [
                'body' => ['invalid_key' => 'invalid_value'],
                'exceptionClass' => UndefinedOptionsException::class,
                'exceptionMessage' => '/The option "invalid_key" does not exist./',
            ],
        ];
    }
}
