<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateVisibilitiesTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class UpdateVisibilitiesTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new UpdateVisibilitiesTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'expectedBody' => []
            ]
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'any data' => [
                'body' => ['key' => 'val'],
                'exceptionClass' => UndefinedOptionsException::class,
                'exceptionMessage' => '/The option "key" does not exist. Defined options are: ""./'
            ]
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro:email:update-visibilities:email-addresses',
            $this->getTopic()->createJobName([])
        );
    }
}
