<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EntityConfigBundle\Async\Topic\AttributeRemovedFromFamilyTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class AttributeRemovedFromFamilyTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new AttributeRemovedFromFamilyTopic();
    }

    public function validBodyDataProvider(): array
    {
        $fullOptionsSet = [
            'attributeFamilyId' => 1,
            'attributeNames' => ['attr1', 'attr2'],
        ];

        return [
            'required options' => [
                'body' => $fullOptionsSet,
                'expectedBody' => $fullOptionsSet,
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "attributeFamilyId", "attributeNames" are missing./',
            ],
            'wrong attributeFamilyId type' => [
                'body' => [
                    'attributeFamilyId' => null,
                    'attributeNames' => [],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "attributeFamilyId" with value null is expected to be of type "string" or "int", '
                    . 'but is of type "null"./',
            ],
            'wrong attributeNames type' => [
                'body' => [
                    'attributeFamilyId' => 1,
                    'attributeNames' => [null, 1, 'string'],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "attributeNames" with value array is expected to be of type "string\[\]", '
                    . 'but one of the elements is of type "null|int"./',
            ],
        ];
    }
}
