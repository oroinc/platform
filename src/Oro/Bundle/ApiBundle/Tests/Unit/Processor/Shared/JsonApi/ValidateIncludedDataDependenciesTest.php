<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\ValidateIncludedDataDependencies;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ValidateIncludedDataDependenciesTest extends FormProcessorTestCase
{
    private ValidateIncludedDataDependencies $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ValidateIncludedDataDependencies();
    }

    private function createValidationError(int $includedObjectIndex): Error
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

    public function testProcessWithoutIncludedData(): void
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

    public function testProcessWithNotArrayData(): void
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

    public function testProcessWithNotArrayIncludedData(): void
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

    public function testProcessWithNotArrayIncludedDataItem(): void
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

    public function testProcessIncludedDataWithoutPrimaryData(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "data" section must exist in the request data.');

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

    public function testProcessDirectToOneRelationship(): void
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

    public function testProcessDirectToManyRelationship(): void
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

    public function testProcessDirectInverseRelationship(): void
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
                    'type'          => 'groups',
                    'id'            => 'included_group_2',
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

    public function testProcessIndirectToOneRelationship(): void
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

    public function testProcessIndirectToManyRelationship(): void
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

    public function testProcessIndirectInverseRelationship(): void
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
                ],
                [
                    'type'          => 'grouptypes',
                    'id'            => 'included_group_type_2',
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

    public function testProcessThirdLevelIndirectInverseRelationshipOn(): void
    {
        $requestData = [
            'data'     => [
                'type' => 'organizations',
                'id'   => 'org_1'
            ],
            'included' => [
                [
                    'type'          => 'users',
                    'id'            => 'included_user_1',
                    'relationships' => [
                        'user' => [
                            'data' => ['type' => 'organizations', 'id' => 'org_1']
                        ]
                    ]
                ],
                [
                    'type'          => 'groups',
                    'id'            => 'included_group_1',
                    'relationships' => [
                        'user' => [
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
                        ]
                    ]
                ],
                [
                    'type'          => 'grouptypes',
                    'id'            => 'included_group_type_2',
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

    public function testProcessMixOfIndirectInverseAndNotInverseRelationship(): void
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

    public function testNullRelationshipShouldBeIgnored(): void
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

    public function testProcessNotRelatedEntity(): void
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

    public function testProcessNotRelatedEntityWithNestedEntity(): void
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

    public function testProcessRelationshipWithoutData(): void
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

    public function testProcessRelationshipWithoutDataButEntityHasOtherValidRelationship(): void
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

    public function testProcessRelationshipWithInvalidData(): void
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

    public function testProcessRelationshipWithInvalidDataButEntityHasOtherValidRelationship(): void
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
}
