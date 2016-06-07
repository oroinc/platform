<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\NormalizeRequestData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class NormalizeRequestDataTest extends FormProcessorTestCase
{
    /** @var NormalizeRequestData */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdTransformer;

    public function setUp()
    {
        parent::setUp();

        $this->entityIdTransformer = $this->getMock('Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface');

        $this->processor = new NormalizeRequestData($this->entityIdTransformer);
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
        $firstName = new FieldMetadata();
        $firstName->setName('firstName');
        $metadata->addField($firstName);
        $lastName = new FieldMetadata();
        $lastName->setName('lastName');
        $metadata->addField($lastName);
        $toOneRelation = new AssociationMetadata();
        $toOneRelation->setName('toOneRelation');
        $toOneRelation->setTargetClassName('Test\User');
        $metadata->addAssociation($toOneRelation);
        $toManyRelation = new AssociationMetadata();
        $toManyRelation->setName('toManyRelation');
        $toManyRelation->setTargetClassName('Test\Group');
        $toManyRelation->setIsCollection(true);
        $metadata->addAssociation($toManyRelation);
        $emptyToOneRelation = new AssociationMetadata();
        $emptyToOneRelation->setName('emptyToOneRelation');
        $emptyToOneRelation->setTargetClassName('Test\User');
        $metadata->addAssociation($emptyToOneRelation);
        $emptyToManyRelation = new AssociationMetadata();
        $emptyToManyRelation->setName('emptyToManyRelation');
        $emptyToManyRelation->setTargetClassName('Test\Group');
        $emptyToManyRelation->setIsCollection(true);
        $metadata->addAssociation($emptyToManyRelation);

        $this->entityIdTransformer->expects($this->any())
            ->method('reverseTransform')
            ->willReturnCallback(
                function ($entityClass, $value) {
                    return 'normalized::' . $entityClass . '::' . $value;
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

    public function testProcessWithInvalidIdentifiers()
    {
        $inputData = [
            'toOneRelation'  => 'val1',
            'toManyRelation' => ['val1', 'val2'],
        ];

        $metadata = new EntityMetadata();
        $toOneRelation = new AssociationMetadata();
        $toOneRelation->setName('toOneRelation');
        $toOneRelation->setTargetClassName('Test\User');
        $metadata->addAssociation($toOneRelation);
        $toManyRelation = new AssociationMetadata();
        $toManyRelation->setName('toManyRelation');
        $toManyRelation->setTargetClassName('Test\Group');
        $toManyRelation->setIsCollection(true);
        $metadata->addAssociation($toManyRelation);

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
                    ->setSource(ErrorSource::createByPropertyPath('toOneRelation')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPropertyPath('toManyRelation/0')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPropertyPath('toManyRelation/1')),
            ],
            $this->context->getErrors()
        );
    }
}
