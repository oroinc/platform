<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailAssociationsTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class UpdateEmailAssociationsTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new UpdateEmailAssociationsTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'expectedBody' => [],
            ],
        ];
    }

    #[\Override]
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

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro.email.update_associations_to_emails',
            $this->getTopic()->createJobName([])
        );
    }
}
