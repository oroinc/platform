<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponsesTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class SendAutoResponsesTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new SendAutoResponsesTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'ids' => [142, 1142],
                ],
                'expectedBody' => [
                    'ids' => [142, 1142],
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'ids' => ['142', '1142'],
                ],
                'expectedBody' => [
                    'ids' => ['142', '1142'],
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
                'exceptionMessage' => '/The option "invalid_key" does not exist. Defined options are: "ids"./',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro.email.send_auto_responses:' . md5(implode(',', ['42', '142'])),
            $this->getTopic()->createJobName(['ids' => ['42', '142']])
        );
    }
}
