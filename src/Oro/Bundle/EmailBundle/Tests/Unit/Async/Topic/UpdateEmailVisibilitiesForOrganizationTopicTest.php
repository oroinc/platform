<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailVisibilitiesForOrganizationTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UpdateEmailVisibilitiesForOrganizationTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new UpdateEmailVisibilitiesForOrganizationTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'full' => [
                'body' => [
                    'organizationId' => 1
                ],
                'expectedBody' => [
                    'organizationId' => 1
                ]
            ]
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "organizationId" is missing./'
            ],
            'invalid organizationId type' => [
                'body' => ['organizationId' => '1'],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "organizationId" with value "1" is expected to be of type "int",'
                    . ' but is of type "string"./'
            ]
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro:email:update-visibilities:emails:42',
            $this->getTopic()->createJobName(['organizationId' => 42])
        );
    }
}
