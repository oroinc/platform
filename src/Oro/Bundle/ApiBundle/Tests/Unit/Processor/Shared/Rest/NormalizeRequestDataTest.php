<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\NormalizeRequestData;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class NormalizeRequestDataTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerInterface */
    private $entityIdTransformer;

    /** @var NormalizeRequestData */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $entityIdTransformerRegistry->expects(self::any())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($this->entityIdTransformer);

        $this->processor = new NormalizeRequestData($entityIdTransformerRegistry);
    }

    private function createFieldMetadata(string $fieldName): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);

        return $fieldMetadata;
    }

    private function createAssociationMetadata(
        string $associationName,
        string $targetClass,
        bool $isCollection
    ): AssociationMetadata {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setIsCollection($isCollection);
        $associationTargetMetadata = new EntityMetadata($targetClass);
        $associationMetadata->setTargetMetadata($associationTargetMetadata);

        return $associationMetadata;
    }

    public function testProcess()
    {
        $inputData = [
            'firstName'           => 'John',
            'lastName'            => 'Doe',
            'toOneRelation'       => '89',
            'toManyRelation'      => ['1', '2', '3'],
            'emptyToOneRelation'  => null,
            'emptyToManyRelation' => []
        ];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->addField($this->createFieldMetadata('firstName'));
        $metadata->addField($this->createFieldMetadata('lastName'));
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\User', false)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', 'Test\Group', true)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('emptyToOneRelation', 'Test\User', false)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('emptyToManyRelation', 'Test\Group', true)
        );

        $this->entityIdTransformer->expects(self::any())
            ->method('reverseTransform')
            ->willReturnCallback(function ($value, EntityMetadata $metadata) {
                return 'normalized::' . $metadata->getClassName() . '::' . $value;
            });

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

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessWithInvalidIdentifiers()
    {
        $inputData = [
            'toOneRelation'  => 'val1',
            'toManyRelation' => ['val1', 'val2']
        ];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\User', false)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', 'Test\Group', true)
        );

        $this->entityIdTransformer->expects(self::any())
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

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPropertyPath('toOneRelation')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPropertyPath('toManyRelation.0')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPropertyPath('toManyRelation.1'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessWithNotResolvedIdentifiers()
    {
        $inputData = [
            'toOneRelation'  => 'val1',
            'toManyRelation' => ['val1', 'val2']
        ];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->addAssociation(
            $this->createAssociationMetadata('toOneRelation', 'Test\User', false)
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('toManyRelation', 'Test\Group', true)
        );

        $this->entityIdTransformer->expects(self::any())
            ->method('reverseTransform')
            ->willReturnCallback(function ($value, EntityMetadata $metadata) {
                if ('val1' === $value) {
                    return null;
                }

                return 'normalized::' . $metadata->getClassName() . '::' . $value;
            });

        $this->context->setRequestData($inputData);
        $this->context->setMetadata($metadata);
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
                'requestData.toOneRelation'    => new NotResolvedIdentifier('val1', 'Test\User'),
                'requestData.toManyRelation.0' => new NotResolvedIdentifier('val1', 'Test\Group')
            ],
            $this->context->getNotResolvedIdentifiers()
        );
    }
}
