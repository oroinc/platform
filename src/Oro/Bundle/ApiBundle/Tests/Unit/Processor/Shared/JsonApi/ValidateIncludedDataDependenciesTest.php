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
    protected $processor;

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
                ],
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
                ],
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
                ],
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(
            [$this->createValidationError(0), $this->createValidationError(1)],
            $this->context->getErrors()
        );
    }

    /**
     * @param int $includedObjectIndex
     *
     * @return Error
     */
    protected function createValidationError($includedObjectIndex)
    {
        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'The entity should have a relationship with the primary entity'
        );
        $error->setSource(
            ErrorSource::createByPointer(sprintf('/included/%s', $includedObjectIndex))
        );

        return $error;
    }
}
