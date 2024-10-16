<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async\Topic;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListFinishTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UpdateListFinishTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new UpdateListFinishTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        $requiredOptionsSet = [
            'entityClass' => '',
            'requestType' => [],
            'version' => '1',
            'operationId' => 1,
            'fileName' => 'foo.bar'
        ];
        $fullOptionsSet = array_merge(
            $requiredOptionsSet,
            [
                'synchronousMode' => true
            ]
        );

        return [
            'only required options' => [
                'body' => $requiredOptionsSet,
                'expectedBody' => array_merge(
                    $requiredOptionsSet,
                    [
                        'synchronousMode' => false
                    ]
                )
            ],
            'full set of options' => [
                'body' => $fullOptionsSet,
                'expectedBody' => $fullOptionsSet
            ]
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "entityClass", "fileName", "operationId", "requestType", ' .
                    '"version" are missing./'
            ],
            'wrong operationId type' => [
                'body' => [
                    'entityClass' => '',
                    'requestType' => [],
                    'operationId' => '1',
                    'fileName' => 'foo.bar',
                    'version' => '1'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "operationId" with value "1" is expected to be of type "int"/'
            ],
            'wrong fileName type' => [
                'body' => [
                    'entityClass' => '',
                    'requestType' => [],
                    'operationId' => 1,
                    'fileName' => 1,
                    'version' => '1'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "fileName" with value 1 is expected to be of type "string"/'
            ],
            'wrong synchronousMode type' => [
                'body' => [
                    'entityClass' => '',
                    'requestType' => [],
                    'operationId' => 1,
                    'fileName' => 1,
                    'version' => 'latest',
                    'synchronousMode' => 1
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "synchronousMode" with value 1 is expected to be of type "bool"/'
            ]
        ];
    }
}
