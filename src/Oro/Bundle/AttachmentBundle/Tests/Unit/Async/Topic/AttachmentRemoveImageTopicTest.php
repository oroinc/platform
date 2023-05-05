<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\AttachmentBundle\Async\Topic\AttachmentRemoveImageTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class AttachmentRemoveImageTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new AttachmentRemoveImageTopic();
    }

    public function validBodyDataProvider(): array
    {
        $requiredOptionsSet = [
            'images' => [
                [
                    'id' => 1,
                    'fileName' => 'foo.bar',
                    'originalFileName'=> 'baz.bar',
                    'parentEntityClass' => \stdClass::class,
                ],
            ],
        ];

        return [
            'only required options' => [
                'body' => $requiredOptionsSet,
                'expectedBody' => $requiredOptionsSet,
            ],
            'originalFileName as null' => [
                'body' => [
                    'images' => [
                        [
                            'id' => 1,
                            'fileName' => 'foo.bar',
                            'originalFileName'=> null,
                            'parentEntityClass' => \stdClass::class,
                        ],
                    ],
                ],
                'expectedBody' => [
                    'images' => [
                        [
                            'id' => 1,
                            'fileName' => 'foo.bar',
                            'originalFileName'=> 'foo.bar',
                            'parentEntityClass' => \stdClass::class,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The nested option "images" is expected to be not empty./',
            ],
            'wrong images type' => [
                'body' => [
                    'images' => new \stdClass(),
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The nested option "images" with value stdClass is expected '
                    . 'to be of type array/',
            ],
            'empty image options' => [
                'body' => [
                    'images' => [
                        [],
                    ],
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "images\[0\]\[fileName\]", "images\[0\]\[id\]",'
                    . ' "images\[0\]\[originalFileName\]", "images\[0\]\[parentEntityClass\]" are missing/',
            ],
            'wrong images.0.id type' => [
                'body' => [
                    'images' => [
                        [
                            'id' => '1',
                            'fileName' => 'foo.bar',
                            'originalFileName'=> 'baz.bar',
                            'parentEntityClass' => \stdClass::class,
                        ],
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "images\[0\]\[id\]" with value "1" is expected to be of type "int",'
                    . ' but is of type "string"/',
            ],
            'wrong images.0.fileName type' => [
                'body' => [
                    'images' => [
                        [
                            'id' => 1,
                            'fileName' => 1,
                            'originalFileName'=> 'baz.bar',
                            'parentEntityClass' => \stdClass::class,
                        ],
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "images\[0\]\[fileName\]" with value 1 is expected to be'
                    . ' of type "string", but is of type "int"/',
            ],
            'wrong images.0.originalFileName type' => [
                'body' => [
                    'images' => [
                        [
                            'id' => 1,
                            'fileName' => 'foo.bar',
                            'originalFileName'=> 1,
                            'parentEntityClass' => \stdClass::class,
                        ],
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "images\[0\]\[originalFileName\]" with value 1 is expected to be'
                    . ' of type "string" or "null", but is of type "int"/',
            ],
            'wrong images.0.parentEntityClass type' => [
                'body' => [
                    'images' => [
                        [
                            'id' => 1,
                            'fileName' => 'foo.bar',
                            'originalFileName'=> 'baz.bar',
                            'parentEntityClass' => 1,
                        ],
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "images\[0\]\[parentEntityClass\]" with value 1 is expected to be'
                    . ' of type "string", but is of type "int"/',
            ],
        ];
    }
}
