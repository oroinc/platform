<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\NormalizeRequestData;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class NormalizeRequestDataTest extends FormProcessorTestCase
{
    /** @var NormalizeRequestData */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdTransformer;

    public function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);

        $this->processor = new NormalizeRequestData($this->valueNormalizer, $this->entityIdTransformer);
    }

    /**
     * @param string $associationName
     * @param string $targetClass
     * @param bool   $isCollection
     *
     * @return AssociationMetadata
     */
    protected function createAssociationMetadata($associationName, $targetClass, $isCollection)
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setAcceptableTargetClassNames([$targetClass]);
        $associationMetadata->setIsCollection($isCollection);
        $associationTargetMetadata = new EntityMetadata();
        $associationTargetMetadata->setClassName($targetClass);
        $associationMetadata->setTargetMetadata($associationTargetMetadata);

        return $associationMetadata;
    }

    public function testProcessForAlreadyNormalizedData()
    {
        $data = ['foo' => 'bar'];
        $this->context->setRequestData($data);
        $this->processor->process($this->context);
        $this->assertSame($data, $this->context->getRequestData());
    }

    public function testProcessWithoutMetadata()
    {
        $inputData = [
            'data' => [
                'attributes'    => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe'
                ],
                'relationships' => [
                    'toOneRelation'       => [
                        'data' => [
                            'type' => 'users',
                            'id'   => '89'
                        ]
                    ],
                    'toManyRelation'      => [
                        'data' => [
                            [
                                'type' => 'groups',
                                'id'   => '1'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '2'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '3'
                            ]
                        ]
                    ],
                    'emptyToOneRelation'  => ['data' => null],
                    'emptyToManyRelation' => ['data' => []]
                ]
            ]
        ];

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['users', 'entityClass', $requestType, false, false, 'Test\User'],
                    ['groups', 'entityClass', $requestType, false, false, 'Test\Group']
                ]
            );
        $this->entityIdTransformer->expects($this->never())
            ->method('reverseTransform');

        $this->context->setRequestData($inputData);
        $this->processor->process($this->context);

        $expectedData = [
            'firstName'           => 'John',
            'lastName'            => 'Doe',
            'toOneRelation'       => [
                'id'    => '89',
                'class' => 'Test\User'
            ],
            'toManyRelation'      => [
                [
                    'id'    => '1',
                    'class' => 'Test\Group'
                ],
                [
                    'id'    => '2',
                    'class' => 'Test\Group'
                ],
                [
                    'id'    => '3',
                    'class' => 'Test\Group'
                ]
            ],
            'emptyToOneRelation'  => [],
            'emptyToManyRelation' => []
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }

    public function testProcessWithMetadata()
    {
        $inputData = [
            'data' => [
                'attributes'    => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe'
                ],
                'relationships' => [
                    'toOneRelation'       => [
                        'data' => [
                            'type' => 'users',
                            'id'   => '89'
                        ]
                    ],
                    'toManyRelation'      => [
                        'data' => [
                            [
                                'type' => 'groups',
                                'id'   => '1'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '2'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '3'
                            ]
                        ]
                    ],
                    'emptyToOneRelation'  => ['data' => null],
                    'emptyToManyRelation' => ['data' => []]
                ]
            ]
        ];

        $metadata = new EntityMetadata();
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\User', false)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', 'Test\Group', true)
        );

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['users', 'entityClass', $requestType, false, false, 'Test\User'],
                    ['groups', 'entityClass', $requestType, false, false, 'Test\Group']
                ]
            );
        $this->entityIdTransformer->expects($this->any())
            ->method('reverseTransform')
            ->willReturnCallback(
                function ($value, EntityMetadata $metadata) {
                    return 'normalized::' . $metadata->getClassName() . '::' . $value;
                }
            );

        $this->context->setRequestData($inputData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $expectedData = [
            'firstName'           => 'John',
            'lastName'            => 'Doe',
            'toOneRelation'       => [
                'id'    => 'normalized::Test\User::89',
                'class' => 'Test\User'
            ],
            'toManyRelation'      => [
                [
                    'id'    => 'normalized::Test\Group::1',
                    'class' => 'Test\Group'
                ],
                [
                    'id'    => 'normalized::Test\Group::2',
                    'class' => 'Test\Group'
                ],
                [
                    'id'    => 'normalized::Test\Group::3',
                    'class' => 'Test\Group'
                ]
            ],
            'emptyToOneRelation'  => [],
            'emptyToManyRelation' => []
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }

    public function testProcessNoAttributes()
    {
        $inputData = [
            'data' => [
                'relationships' => [
                    'toOneRelation' => [
                        'data' => [
                            'type' => 'users',
                            'id'   => '89'
                        ]
                    ]
                ]
            ]
        ];

        $metadata = new EntityMetadata();
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\User', false)
        );

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['users', 'entityClass', $requestType, false, false, 'Test\User'],
                ]
            );
        $this->entityIdTransformer->expects($this->any())
            ->method('reverseTransform')
            ->willReturnCallback(
                function ($value, EntityMetadata $metadata) {
                    return 'normalized::' . $metadata->getClassName() . '::' . $value;
                }
            );

        $this->context->setRequestData($inputData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $expectedData = [
            'toOneRelation' => [
                'id'    => 'normalized::Test\User::89',
                'class' => 'Test\User'
            ]
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }

    public function testProcessWithInvalidEntityTypes()
    {
        $inputData = [
            'data' => [
                'relationships' => [
                    'toOneRelation'  => [
                        'data' => [
                            'type' => 'users',
                            'id'   => '89'
                        ]
                    ],
                    'toManyRelation' => [
                        'data' => [
                            [
                                'type' => 'groups',
                                'id'   => '1'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '2'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willThrowException(new \Exception('cannot normalize entity type'));
        $this->entityIdTransformer->expects($this->never())
            ->method('reverseTransform');

        $this->context->setRequestData($inputData);
        $this->processor->process($this->context);

        $expectedData = [
            'toOneRelation'  => [
                'id'    => '89',
                'class' => 'users'
            ],
            'toManyRelation' => [
                [
                    'id'    => '1',
                    'class' => 'groups'
                ],
                [
                    'id'    => '2',
                    'class' => 'groups'
                ]
            ]
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
        $this->assertEquals(
            [
                Error::createValidationError('entity type constraint')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toOneRelation/data/type')),
                Error::createValidationError('entity type constraint')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/0/type')),
                Error::createValidationError('entity type constraint')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/1/type')),
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithNotAcceptableEntityTypes()
    {
        $inputData = [
            'data' => [
                'relationships' => [
                    'toOneRelation'  => [
                        'data' => [
                            'type' => 'users',
                            'id'   => '89'
                        ]
                    ],
                    'toManyRelation' => [
                        'data' => [
                            [
                                'type' => 'groups',
                                'id'   => '1'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '2'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $metadata = new EntityMetadata();
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\AnotherUser', false)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', 'Test\AnotherGroup', true)
        );

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['users', 'entityClass', $this->context->getRequestType(), false, false, 'Test\User'],
                    ['groups', 'entityClass', $this->context->getRequestType(), false, false, 'Test\Group']
                ]
            );
        $this->entityIdTransformer->expects($this->never())
            ->method('reverseTransform');

        $this->context->setRequestData($inputData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $expectedData = [
            'toOneRelation'  => [
                'id'    => '89',
                'class' => 'Test\User'
            ],
            'toManyRelation' => [
                [
                    'id'    => '1',
                    'class' => 'Test\Group'
                ],
                [
                    'id'    => '2',
                    'class' => 'Test\Group'
                ]
            ]
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
        $this->assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toOneRelation/data/type')),
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/0/type')),
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/1/type')),
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithEmptyAcceptableEntityTypes()
    {
        $inputData = [
            'data' => [
                'relationships' => [
                    'toOneRelation'  => [
                        'data' => [
                            'type' => 'users',
                            'id'   => '89'
                        ]
                    ],
                    'toManyRelation' => [
                        'data' => [
                            [
                                'type' => 'groups',
                                'id'   => '1'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '2'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $metadata = new EntityMetadata();
        $toOneRelation = $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', EntityIdentifier::class, false)
        );
        $toOneRelation->setAcceptableTargetClassNames([]);
        $toManyRelation = $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', EntityIdentifier::class, true)
        );
        $toManyRelation->setAcceptableTargetClassNames([]);

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['users', 'entityClass', $this->context->getRequestType(), false, false, 'Test\User'],
                    ['groups', 'entityClass', $this->context->getRequestType(), false, false, 'Test\Group']
                ]
            );
        $this->entityIdTransformer->expects($this->any())
            ->method('reverseTransform')
            ->willReturnCallback(
                function ($value, EntityMetadata $metadata) {
                    return 'normalized::' . $metadata->getClassName() . '::' . $value;
                }
            );

        $this->context->setRequestData($inputData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $expectedData = [
            'toOneRelation'  => [
                'id'    => 'normalized::' . EntityIdentifier::class . '::89',
                'class' => 'Test\User'
            ],
            'toManyRelation' => [
                [
                    'id'    => 'normalized::' . EntityIdentifier::class . '::1',
                    'class' => 'Test\Group'
                ],
                [
                    'id'    => 'normalized::' . EntityIdentifier::class . '::2',
                    'class' => 'Test\Group'
                ]
            ]
        ];

        $this->assertFalse($this->context->hasErrors());
        $this->assertEquals($expectedData, $this->context->getRequestData());
    }

    public function testProcessWithEmptyAcceptableEntityTypesShouldBeRejected()
    {
        $inputData = [
            'data' => [
                'relationships' => [
                    'toOneRelation'  => [
                        'data' => [
                            'type' => 'users',
                            'id'   => '89'
                        ]
                    ],
                    'toManyRelation' => [
                        'data' => [
                            [
                                'type' => 'groups',
                                'id'   => '1'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '2'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $metadata = new EntityMetadata();
        $toOneRelation = $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', EntityIdentifier::class, false)
        );
        $toOneRelation->setAcceptableTargetClassNames([]);
        $toOneRelation->setEmptyAcceptableTargetsAllowed(false);
        $toManyRelation = $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', EntityIdentifier::class, true)
        );
        $toManyRelation->setAcceptableTargetClassNames([]);
        $toManyRelation->setEmptyAcceptableTargetsAllowed(false);

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['users', 'entityClass', $this->context->getRequestType(), false, false, 'Test\User'],
                    ['groups', 'entityClass', $this->context->getRequestType(), false, false, 'Test\Group']
                ]
            );
        $this->entityIdTransformer->expects($this->any())
            ->method('reverseTransform')
            ->willReturnCallback(
                function ($value, EntityMetadata $metadata) {
                    return 'normalized::' . $metadata->getClassName() . '::' . $value;
                }
            );

        $this->context->setRequestData($inputData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $expectedData = [
            'toOneRelation'  => [
                'id'    => '89',
                'class' => 'Test\User'
            ],
            'toManyRelation' => [
                [
                    'id'    => '1',
                    'class' => 'Test\Group'
                ],
                [
                    'id'    => '2',
                    'class' => 'Test\Group'
                ]
            ]
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
        $this->assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toOneRelation/data/type')),
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/0/type')),
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/1/type')),
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidIdentifiers()
    {
        $inputData = [
            'data' => [
                'relationships' => [
                    'toOneRelation'  => [
                        'data' => [
                            'type' => 'users',
                            'id'   => 'val1'
                        ]
                    ],
                    'toManyRelation' => [
                        'data' => [
                            [
                                'type' => 'groups',
                                'id'   => 'val1'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => 'val2'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $metadata = new EntityMetadata();
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\User', false)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', 'Test\Group', true)
        );

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['users', 'entityClass', $this->context->getRequestType(), false, false, 'Test\User'],
                    ['groups', 'entityClass', $this->context->getRequestType(), false, false, 'Test\Group']
                ]
            );
        $this->entityIdTransformer->expects($this->any())
            ->method('reverseTransform')
            ->willThrowException(new \Exception('cannot normalize id'));

        $this->context->setRequestData($inputData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $expectedData = [
            'toOneRelation'  => [
                'id'    => 'val1',
                'class' => 'Test\User'
            ],
            'toManyRelation' => [
                [
                    'id'    => 'val1',
                    'class' => 'Test\Group'
                ],
                [
                    'id'    => 'val2',
                    'class' => 'Test\Group'
                ]
            ]
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
        $this->assertEquals(
            [
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toOneRelation/data/id')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/0/id')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/1/id')),
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessShouldNotNormalizeIdOfIncludedEntity()
    {
        $inputData = [
            'data' => [
                'relationships' => [
                    'association' => [
                        'data' => [
                            'type' => 'users',
                            'id'   => 'INCLUDED1'
                        ]
                    ]
                ]
            ]
        ];
        $includedEntities = new IncludedEntityCollection();
        $includedEntities->add(new \stdClass(), 'Test\User', 'INCLUDED1', new IncludedEntityData('/included/0', 0));

        $metadata = new EntityMetadata();
        $metadata->addAssociation(
            $this->createAssociationMetadata('association', 'Test\User', false)
        );

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with('users', 'entityClass', $requestType, false, false)
            ->willReturn('Test\User');
        $this->entityIdTransformer->expects($this->never())
            ->method('reverseTransform');

        $this->context->setRequestData($inputData);
        $this->context->setMetadata($metadata);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        $expectedData = [
            'association' => [
                'id'    => 'INCLUDED1',
                'class' => 'Test\User'
            ],
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }

    public function testProcessShouldNotNormalizeIdOfIncludedPrimaryEntity()
    {
        $inputData = [
            'data' => [
                'relationships' => [
                    'association' => [
                        'data' => [
                            'type' => 'users',
                            'id'   => 'PRIMARY1'
                        ]
                    ]
                ]
            ]
        ];
        $includedEntities = new IncludedEntityCollection();
        $includedEntities->setPrimaryEntityId('Test\User', 'PRIMARY1');

        $metadata = new EntityMetadata();
        $metadata->addAssociation(
            $this->createAssociationMetadata('association', 'Test\User', false)
        );

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with('users', 'entityClass', $requestType, false, false)
            ->willReturn('Test\User');
        $this->entityIdTransformer->expects($this->never())
            ->method('reverseTransform');

        $this->context->setRequestData($inputData);
        $this->context->setMetadata($metadata);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        $expectedData = [
            'association' => [
                'id'    => 'PRIMARY1',
                'class' => 'Test\User'
            ],
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }
}
