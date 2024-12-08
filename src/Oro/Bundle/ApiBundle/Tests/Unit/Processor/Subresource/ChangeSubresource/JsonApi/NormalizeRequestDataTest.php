<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeSubresource\JsonApi;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\JsonApi\NormalizeRequestData;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeSubresourceProcessorTestCase;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NormalizeRequestDataTest extends ChangeSubresourceProcessorTestCase
{
    /** @var ValueNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $valueNormalizer;

    /** @var EntityIdTransformerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityIdTransformer;

    /** @var NormalizeRequestData */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $entityIdTransformerRegistry->expects(self::any())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($this->entityIdTransformer);

        $this->processor = new NormalizeRequestData($this->valueNormalizer, $entityIdTransformerRegistry);
    }

    protected function createAssociationMetadata(
        string $associationName,
        string $targetClass,
        bool $isCollection
    ): AssociationMetadata {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setAcceptableTargetClassNames([$targetClass]);
        $associationMetadata->setIsCollection($isCollection);
        $associationTargetMetadata = new EntityMetadata($targetClass);
        $associationTargetMetadata->setIdentifierFieldNames(['id']);
        $associationMetadata->setTargetMetadata($associationTargetMetadata);

        return $associationMetadata;
    }

    public function testProcessWhenRequestDataAlreadyNormalized(): void
    {
        $inputData = ['foo' => 'bar'];

        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($inputData);
        $this->context->setProcessed(NormalizeRequestData::OPERATION_NAME);
        $this->processor->process($this->context);

        self::assertEquals($inputData, $this->context->getRequestData());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessForAlreadyNormalizedData(): void
    {
        $inputData = ['foo' => 'bar'];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($inputData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessWithoutMetadata(): void
    {
        $inputData = [
            'data' => [
                'meta'          => [
                    'meta1' => 'val1'
                ],
                'attributes'    => [
                    'name' => 'John'
                ],
                'relationships' => [
                    'users' => [
                        'data' => []
                    ]
                ]
            ]
        ];

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata(null);
        $this->processor->process($this->context);

        self::assertEquals($inputData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWithMetadata(): void
    {
        $inputData = [
            'data' => [
                'meta'          => [
                    'meta1' => 'val1',
                    'meta2' => 'val2'
                ],
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

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addMetaProperty(new MetaPropertyMetadata('meta1'));
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\User', false)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', 'Test\Group', true)
        );

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->willReturnMap([
                ['users', 'entityClass', $requestType, false, false, [], 'Test\User'],
                ['groups', 'entityClass', $requestType, false, false, [], 'Test\Group']
            ]);
        $this->entityIdTransformer->expects(self::any())
            ->method('reverseTransform')
            ->willReturnCallback(function ($value, EntityMetadata $metadata) {
                return 'normalized::' . $metadata->getClassName() . '::' . $value;
            });

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata($metadata);
        $this->processor->process($this->context);

        $expectedData = [
            'meta1'               => 'val1',
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

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessNoAttributes(): void
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

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\User', false)
        );

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->willReturnMap([
                ['users', 'entityClass', $requestType, false, false, [], 'Test\User']
            ]);
        $this->entityIdTransformer->expects(self::any())
            ->method('reverseTransform')
            ->willReturnCallback(function ($value, EntityMetadata $metadata) {
                return 'normalized::' . $metadata->getClassName() . '::' . $value;
            });

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata($metadata);
        $this->processor->process($this->context);

        $expectedData = [
            'toOneRelation' => [
                'id'    => 'normalized::Test\User::89',
                'class' => 'Test\User'
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessWithInvalidEntityTypes(): void
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

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->willThrowException(new EntityAliasNotFoundException('cannot normalize entity type'));
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata($metadata);
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

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Unknown entity type: users.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toOneRelation/data/type')),
                Error::createValidationError('entity type constraint', 'Unknown entity type: groups.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/0/type')),
                Error::createValidationError('entity type constraint', 'Unknown entity type: groups.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/1/type'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessWithNotAcceptableEntityTypes(): void
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

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\AnotherUser', false)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', 'Test\AnotherGroup', true)
        );

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->willReturnMap([
                ['users', 'entityClass', $this->context->getRequestType(), false, false, [], 'Test\User'],
                ['groups', 'entityClass', $this->context->getRequestType(), false, false, [], 'Test\Group']
            ]);
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata($metadata);
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

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toOneRelation/data/type')),
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/0/type')),
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/1/type'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessWithEmptyAcceptableEntityTypes(): void
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

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $toOneRelation = $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', EntityIdentifier::class, false)
        );
        $toOneRelation->setAcceptableTargetClassNames([]);
        $toManyRelation = $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', EntityIdentifier::class, true)
        );
        $toManyRelation->setAcceptableTargetClassNames([]);

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->willReturnMap([
                ['users', 'entityClass', $this->context->getRequestType(), false, false, [], 'Test\User'],
                ['groups', 'entityClass', $this->context->getRequestType(), false, false, [], 'Test\Group']
            ]);
        $this->entityIdTransformer->expects(self::any())
            ->method('reverseTransform')
            ->willReturnCallback(function ($value, EntityMetadata $metadata) {
                return 'normalized::' . $metadata->getClassName() . '::' . $value;
            });

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata($metadata);
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

        self::assertFalse($this->context->hasErrors());
        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessWithEmptyAcceptableEntityTypesShouldBeRejected(): void
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

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
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

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->willReturnMap([
                ['users', 'entityClass', $this->context->getRequestType(), false, false, [], 'Test\User'],
                ['groups', 'entityClass', $this->context->getRequestType(), false, false, [], 'Test\Group']
            ]);
        $this->entityIdTransformer->expects(self::any())
            ->method('reverseTransform')
            ->willReturnCallback(function ($value, EntityMetadata $metadata) {
                return 'normalized::' . $metadata->getClassName() . '::' . $value;
            });

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata($metadata);
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

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toOneRelation/data/type')),
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/0/type')),
                Error::createValidationError('entity type constraint', 'Not acceptable entity type.')
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/1/type'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessWithInvalidIdentifiers(): void
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

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\User', false)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', 'Test\Group', true)
        );

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->willReturnMap([
                ['users', 'entityClass', $this->context->getRequestType(), false, false, [], 'Test\User'],
                ['groups', 'entityClass', $this->context->getRequestType(), false, false, [], 'Test\Group']
            ]);
        $this->entityIdTransformer->expects(self::any())
            ->method('reverseTransform')
            ->willThrowException(new \Exception('cannot normalize id'));

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata($metadata);
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

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toOneRelation/data/id')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/0/id')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/relationships/toManyRelation/data/1/id'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessWithNotResolvedIdentifiers(): void
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

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\User', false)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', 'Test\Group', true)
        );

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->willReturnMap([
                ['users', 'entityClass', $this->context->getRequestType(), false, false, [], 'Test\User'],
                ['groups', 'entityClass', $this->context->getRequestType(), false, false, [], 'Test\Group']
            ]);
        $this->entityIdTransformer->expects(self::any())
            ->method('reverseTransform')
            ->willReturnCallback(function ($value, EntityMetadata $metadata) {
                if ('val1' === $value) {
                    return null;
                }

                return 'normalized::' . $metadata->getClassName() . '::' . $value;
            });

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata($metadata);
        $this->processor->process($this->context);

        $expectedData = [
            'toOneRelation'  => [
                'id'    => null,
                'class' => 'Test\User'
            ],
            'toManyRelation' => [
                [
                    'id'    => null,
                    'class' => 'Test\Group'
                ],
                [
                    'id'    => 'normalized::Test\Group::val2',
                    'class' => 'Test\Group'
                ]
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertFalse($this->context->hasErrors());
        self::assertEquals(
            [
                'requestData.toOneRelation.id'    => new NotResolvedIdentifier('val1', 'Test\User'),
                'requestData.toManyRelation.0.id' => new NotResolvedIdentifier('val1', 'Test\Group')
            ],
            $this->context->getNotResolvedIdentifiers()
        );
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessShouldNotNormalizeIdOfIncludedEntity(): void
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

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addAssociation(
            $this->createAssociationMetadata('association', 'Test\User', false)
        );

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', 'entityClass', $requestType, false, false, [])
            ->willReturn('Test\User');
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata($metadata);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        $expectedData = [
            'association' => [
                'id'    => 'INCLUDED1',
                'class' => 'Test\User'
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessShouldNotNormalizeIdOfIncludedPrimaryEntity(): void
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

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addAssociation(
            $this->createAssociationMetadata('association', 'Test\User', false)
        );

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', 'entityClass', $requestType, false, false, [])
            ->willReturn('Test\User');
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($inputData);
        $this->context->setRequestMetadata($metadata);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        $expectedData = [
            'association' => [
                'id'    => 'PRIMARY1',
                'class' => 'Test\User'
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessForEntityThatDoesNotHaveIdentifierFields(): void
    {
        $requestData = ['meta' => ['foo' => 'bar']];

        $this->context->setRequestData($requestData);
        $this->context->setRequestMetadata(new EntityMetadata('Test\Entity'));
        $this->processor->process($this->context);

        self::assertSame($requestData['meta'], $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }

    public function testProcessForEntityThatDoesNotHaveIdentifierFieldsAndNoMetaSectionInRequestData(): void
    {
        $requestData = ['another' => ['foo' => 'bar']];

        $this->context->setRequestData($requestData);
        $this->context->setRequestMetadata(new EntityMetadata('Test\Entity'));
        $this->processor->process($this->context);

        self::assertSame($requestData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
        self::assertTrue($this->context->isProcessed(NormalizeRequestData::OPERATION_NAME));
    }
}
