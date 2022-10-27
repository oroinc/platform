<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailVisibilitiesForOrganizationChunkTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UpdateEmailVisibilitiesForOrganizationChunkTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new UpdateEmailVisibilitiesForOrganizationChunkTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'full' => [
                'body' => [
                    'jobId' => 1,
                    'organizationId' => 2,
                    'firstEmailId' => 3,
                    'lastEmailId' => 4
                ],
                'expectedBody' => [
                    'jobId' => 1,
                    'organizationId' => 2,
                    'firstEmailId' => 3,
                    'lastEmailId' => 4
                ]
            ],
            'null lastEmailId' => [
                'body' => [
                    'jobId' => 1,
                    'organizationId' => 2,
                    'firstEmailId' => 3,
                    'lastEmailId' => null
                ],
                'expectedBody' => [
                    'jobId' => 1,
                    'organizationId' => 2,
                    'firstEmailId' => 3,
                    'lastEmailId' => null
                ]
            ],
            'no lastEmailId' => [
                'body' => [
                    'jobId' => 1,
                    'organizationId' => 2,
                    'firstEmailId' => 3
                ],
                'expectedBody' => [
                    'jobId' => 1,
                    'organizationId' => 2,
                    'firstEmailId' => 3,
                    'lastEmailId' => null
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
                'exceptionMessage' => '/The required options "firstEmailId", "jobId", "organizationId" are missing./'
            ],
            'invalid jobId type' => [
                'body' => ['jobId' => '1', 'organizationId' => 2, 'firstEmailId' => 3, 'lastEmailId' => 4],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "jobId" with value "1" is expected to be of type "int",'
                    . ' but is of type "string"./'
            ],
            'invalid organizationId type' => [
                'body' => ['jobId' => 1, 'organizationId' => '2', 'firstEmailId' => 3, 'lastEmailId' => 4],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "organizationId" with value "2" is expected to be of type "int",'
                    . ' but is of type "string"./'
            ],
            'invalid firstEmailId type' => [
                'body' => ['jobId' => 1, 'organizationId' => 2, 'firstEmailId' => '3', 'lastEmailId' => 4],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "firstEmailId" with value "3" is expected to be of type "int",'
                    . ' but is of type "string"./'
            ],
            'invalid lastEmailId type' => [
                'body' => ['jobId' => 1, 'organizationId' => 2, 'firstEmailId' => 3, 'lastEmailId' => '4'],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "lastEmailId" with value "4" is expected to be of type'
                    . ' "int" or "null", but is of type "string"./'
            ]
        ];
    }
}
