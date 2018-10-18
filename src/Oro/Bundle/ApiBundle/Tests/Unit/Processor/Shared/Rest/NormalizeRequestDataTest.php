<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
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

    protected function setUp()
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

    /**
     * @param string $fieldName
     *
     * @return FieldMetadata
     */
    private function createFieldMetadata($fieldName)
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);

        return $fieldMetadata;
    }

    /**
     * @param string $associationName
     * @param string $targetClass
     * @param bool   $isCollection
     *
     * @return AssociationMetadata
     */
    private function createAssociationMetadata($associationName, $targetClass, $isCollection)
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setIsCollection($isCollection);
        $associationTargetMetadata = new EntityMetadata();
        $associationTargetMetadata->setClassName($targetClass);
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

        $metadata = new EntityMetadata();
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

        self::assertEquals($expectedData, $this->context->getRequestData());
    }

    public function testProcessWithInvalidIdentifiers()
    {
        $inputData = [
            'toOneRelation'  => 'val1',
            'toManyRelation' => ['val1', 'val2']
        ];

        $metadata = new EntityMetadata();
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
    }
}
