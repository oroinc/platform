<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class PurgeEmailAttachmentsTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new PurgeEmailAttachmentsTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'expectedBody' => [
                    'all' => false,
                    'size' => null,
                ],
            ],
            'all options' => [
                'body' => [
                    'all' => true,
                    'size' => 101,
                ],
                'expectedBody' => [
                    'all' => true,
                    'size' => 101,
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'all' => 1,
                    'size' => null,
                ],
                'expectedBody' => [
                    'all' => 1,
                    'size' => null,
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
                'exceptionMessage' => '/The option "invalid_key" does not exist. Defined options are: "all", "size"./',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro.email.purge_email_attachments',
            $this->getTopic()->createJobName([])
        );
    }
}
