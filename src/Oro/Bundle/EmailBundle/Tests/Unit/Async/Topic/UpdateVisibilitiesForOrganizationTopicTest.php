<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateVisibilitiesForOrganizationTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UpdateVisibilitiesForOrganizationTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new UpdateVisibilitiesForOrganizationTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'full' => [
                'body' => [
                    'jobId' => 1,
                    'organizationId' => 2
                ],
                'expectedBody' => [
                    'jobId' => 1,
                    'organizationId' => 2
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
                'exceptionMessage' => '/The required options "jobId", "organizationId" are missing./'
            ],
            'invalid jobId type' => [
                'body' => ['jobId' => '1', 'organizationId' => 2],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "jobId" with value "1" is expected to be of type "int",'
                    . ' but is of type "string"./'
            ],
            'invalid organizationId type' => [
                'body' => ['jobId' => 1, 'organizationId' => '2'],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "organizationId" with value "2" is expected to be of type "int",'
                    . ' but is of type "string"./'
            ]
        ];
    }
}
