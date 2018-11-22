<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\ValidateIncludedDataDependencies;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class ValidateIncludedDataDependenciesTest extends FormProcessorTestCase
{
    /** @var ValidateIncludedDataDependencies */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateIncludedDataDependencies();
    }

    public function testProcessWithoutIncludedData()
    {
        $requestData = [
            'data' => [
                'type' => 'users'
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithNotArrayData()
    {
        $requestData = [
            'data'     => 'test',
            'included' => [
                [
                    'type' => 'groups',
                    'id'   => 'included_group_1'
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithNotArrayIncludedData()
    {
        $requestData = [
            'data' => [
                'type' => 'users'
            ],
            'included' => 'test'
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithNotArrayIncludedDataItem()
    {
        $requestData = [
            'data' => [
                'type' => 'users'
            ],
            'included' => [
                'test'
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(
            [$this->createValidationError(0)],
            $this->context->getErrors()
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The "data" section must exist in the request data.
     */
    public function testProcessIncludedDataWithoutPrimaryData()
    {
        $requestData = [
            'included' => [
                [
                    'type' => 'groups',
                    'id'   => 'included_group_1'
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);
    }

    public function testProcessDirectToOneRelationship()
    {
        $requestData = [
            'data'     => [
                'type'          => 'users',
                'relationships' => [
                    'group' => [
                        'data' => ['type' => 'groups', 'id' => 'included_group_1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'groups',
                    'id'   => 'included_group_1'
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessDirectToManyRelationship()
    {
        $requestData = [
            'data'     => [
                'type'          => 'users',
                'relationships' => [
                    'groups' => [
                        'data' => [
                            ['type' => 'groups', 'id' => 'included_group_1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'groups',
                    'id'   => 'included_group_1'
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessDirectInverseRelationship()
    {
        $requestData = [
            'data'     => [
                'type' => 'users',
                'id'   => 'user_1'
            ],
            'included' => [
                [
                    'type'          => 'groups',
                    'id'            => 'included_group_1',
                    'relationships' => [
                        'user' => [
                            'data' => ['type' => 'users', 'id' => 'user_1']
                        ]
                    ]
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessIndirectToOneRelationship()
    {
        $requestData = [
            'data'     => [
                'type'          => 'users',
                'relationships' => [
                    'group' => [
                        'data' => ['type' => 'groups', 'id' => 'included_group_1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'groups',
                    'id'            => 'included_group_1',
                    'relationships' => [
                        'groupType' => [
                            'data' => ['type' => 'grouptypes', 'id' => 'included_group_type_1']
                        ]
                    ]
                ],
                [
                    'type' => 'grouptypes',
                    'id'   => 'included_group_type_1'
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessIndirectToManyRelationship()
    {
        $requestData = [
            'data'     => [
                'type'          => 'users',
                'relationships' => [
                    'groups' => [
                        'data' => [
                            ['type' => 'groups', 'id' => 'included_group_1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'groups',
                    'id'            => 'included_group_1',
                    'relationships' => [
                        'groupTypes' => [
                            'data' => [
                                ['type' => 'grouptypes', 'id' => 'included_group_type_1']
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'grouptypes',
                    'id'   => 'included_group_type_1'
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessIndirectInverseRelationship()
    {
        $requestData = [
            'data'     => [
                'type' => 'users',
                'id'   => 'user_1'
            ],
            'included' => [
                [
                    'type'          => 'groups',
                    'id'            => 'included_group_1',
                    'relationships' => [
                        'user' => [
                            'data' => ['type' => 'users', 'id' => 'user_1']
                        ]
                    ]
                ],
                [
                    'type'          => 'grouptypes',
                    'id'            => 'included_group_type_1',
                    'relationships' => [
                        'group' => [
                            'data' => ['type' => 'groups', 'id' => 'included_group_1']
                        ]
                    ]
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessMixOfIndirectInverseAndNotInverseRelationship()
    {
        $requestData = [
            'data'     => [
                'type'          => 'users',
                'relationships' => [
                    'group' => [
                        'data' => ['type' => 'groups', 'id' => 'included_group_1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'groups',
                    'id'            => 'included_group_1',
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => 'included_user_1']
                        ]
                    ]
                ],
                [
                    'type'          => 'grouptypes',
                    'id'            => 'included_group_type_1',
                    'relationships' => [
                        'group' => [
                            'data' => ['type' => 'groups', 'id' => 'included_group_1']
                        ],
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => 'included_user_1']
                        ]
                    ]
                ],
                [
                    'type'          => 'users',
                    'id'            => 'included_user_1',
                    'relationships' => [
                        'group' => [
                            'data' => ['type' => 'groups', 'id' => 'included_group_2']
                        ]
                    ]
                ],
                [
                    'type' => 'groups',
                    'id'   => 'included_group_2'
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testNullRelationshipShouldBeIgnored()
    {
        $requestData = [
            'data'     => [
                'type'          => 'users',
                'relationships' => [
                    'organization' => [
                        'data' => null
                    ],
                    'group'        => [
                        'data' => ['type' => 'groups', 'id' => 'included_group_1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'groups',
                    'id'   => 'included_group_1'
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessNotRelatedEntity()
    {
        $requestData = [
            'data'     => [
                'type'          => 'users',
                'relationships' => [
                    'group' => [
                        'data' => ['type' => 'groups', 'id' => '1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'groups',
                    'id'   => 'included_group_1'
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(
            [$this->createValidationError(0)],
            $this->context->getErrors()
        );
    }

    public function testProcessNotRelatedEntityWithNestedEntity()
    {
        $requestData = [
            'data'     => [
                'type'          => 'users',
                'relationships' => [
                    'group' => [
                        'data' => ['type' => 'groups', 'id' => '1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'groups',
                    'id'            => 'included_group_1',
                    'relationships' => [
                        'groupType' => [
                            'data' => ['type' => 'grouptypes', 'id' => 'included_group_type_1']
                        ]
                    ]
                ],
                [
                    'type' => 'grouptypes',
                    'id'   => 'included_group_type_1'
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(
            [$this->createValidationError(0), $this->createValidationError(1)],
            $this->context->getErrors()
        );
    }

    public function testProcessRelationshipWithoutData()
    {
        $requestData = [
            'data'     => [
                'type' => 'users',
                'id'   => 'user_1'
            ],
            'included' => [
                [
                    'type'          => 'groups',
                    'id'            => 'included_group_1',
                    'relationships' => [
                        'user' => ['type' => 'users', 'id' => 'user_1']
                    ]
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(
            [$this->createValidationError(0)],
            $this->context->getErrors()
        );
    }

    public function testProcessRelationshipWithoutDataButEntityHasOtherValidRelationship()
    {
        $requestData = [
            'data'     => [
                'type' => 'users',
                'id'   => 'user_1'
            ],
            'included' => [
                [
                    'type'          => 'groups',
                    'id'            => 'included_group_1',
                    'relationships' => [
                        'user1' => ['type' => 'users', 'id' => 'user_1'],
                        'user2' => [
                            'data' => ['type' => 'users', 'id' => 'user_1']
                        ]
                    ]
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessRelationshipWithInvalidData()
    {
        $requestData = [
            'data'     => [
                'type' => 'users',
                'id'   => 'user_1'
            ],
            'included' => [
                [
                    'type'          => 'groups',
                    'id'            => 'included_group_1',
                    'relationships' => [
                        'user' => ['data' => 'invalid']
                    ]
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(
            [$this->createValidationError(0)],
            $this->context->getErrors()
        );
    }

    public function testProcessRelationshipWithInvalidDataButEntityHasOtherValidRelationship()
    {
        $requestData = [
            'data'     => [
                'type' => 'users',
                'id'   => 'user_1'
            ],
            'included' => [
                [
                    'type'          => 'groups',
                    'id'            => 'included_group_1',
                    'relationships' => [
                        'user1' => ['data' => 'invalid'],
                        'user2' => [
                            'data' => ['type' => 'users', 'id' => 'user_1']
                        ]
                    ]
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    /**
     * @param int $includedObjectIndex
     *
     * @return Error
     */
    private function createValidationError($includedObjectIndex)
    {
        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'The entity should have a relationship with the primary entity'
            . ' and this should be explicitly specified in the request'
        );
        $error->setSource(
            ErrorSource::createByPointer(sprintf('/included/%s', $includedObjectIndex))
        );

        return $error;
    }
}
