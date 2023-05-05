<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ImapBundle\Async\Topic\ClearInactiveMailboxTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class ClearInactiveMailboxTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ClearInactiveMailboxTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'expectedBody' => [],
            ],
            'all options' => [
                'body' => [
                    'id' => 42,
                ],
                'expectedBody' => [
                    'id' => 42,
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'id' => '42',
                ],
                'expectedBody' => [
                    'id' => '42',
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
                'exceptionMessage' => '/The option "invalid_key" does not exist. Defined options are: "id"./',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro.imap.clear_inactive_mailbox',
            $this->getTopic()->createJobName([])
        );
    }
}
