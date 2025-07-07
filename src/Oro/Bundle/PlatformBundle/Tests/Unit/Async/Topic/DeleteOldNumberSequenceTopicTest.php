<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\PlatformBundle\Async\Topic\DeleteOldNumberSequenceTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

final class DeleteOldNumberSequenceTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new DeleteOldNumberSequenceTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            [
                'body' => ['sequenceType' => 'sample-name', 'discriminatorType' => 'sample-name'],
                'expectedBody' => ['sequenceType' => 'sample-name', 'discriminatorType' => 'sample-name'],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "discriminatorType", "sequenceType" are missing./',
            ],
        ];
    }
}
