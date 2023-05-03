<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationsTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UpdateEmailOwnerAssociationsTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new UpdateEmailOwnerAssociationsTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'ownerClass' => \stdClass::class,
                    'ownerIds' => [42, 142],
                ],
                'expectedBody' => [
                    'ownerClass' => \stdClass::class,
                    'ownerIds' => [42, 142],
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'ownerClass' => \stdClass::class,
                    'ownerIds' => ['42', '142'],
                ],
                'expectedBody' => [
                    'ownerClass' => \stdClass::class,
                    'ownerIds' => ['42', '142'],
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
                'exceptionMessage' => '/The required options "ownerClass", "ownerIds" are missing./',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro.email.update_email_owner_associations:class:'.md5(implode(',', ['42', '142'])),
            $this->getTopic()->createJobName(['ownerClass' => 'class', 'ownerIds' => ['42', '142']])
        );
    }
}
