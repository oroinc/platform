<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldData;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\SourceEntityValidator;

class SourceEntityValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SourceEntityValidator
     */
    protected $validator;

    protected function setUp()
    {
        $doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper
            ->expects($this->any())
            ->method('isEntityEqual')
            ->will(
                $this->returnCallback(
                    function ($entity, $other) {
                        return $entity->getId() === $other->getId();
                    }
                )
            );

        $this->validator = new SourceEntityValidator($doctrineHelper);
    }

    /**
     * @dataProvider invalidArgumentProvider
     */
    public function testInvalidArgument($value, $expectedExceptionMessage)
    {
        $this->setExpectedException(
            'Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException',
            $expectedExceptionMessage
        );

        $constraint = $this
            ->getMock('Oro\Bundle\EntityMergeBundle\Validator\Constraints\SourceEntity');
        $this->validator->validate($value, $constraint);
    }

    public function invalidArgumentProvider()
    {
        return [
            'bool'    => [
                'value'                    => true,
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, boolean given'
            ],
            'string'  => [
                'value'                    => 'string',
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, string given'
            ],
            'integer' => [
                'value'                    => 5,
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, integer given'
            ],
            'null'    => [
                'value'                    => null,
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, NULL given'
            ],
            'object'  => [
                'value'                    => new \stdClass(),
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, stdClass given'
            ],
            'array'   => [
                'value'                    => [],
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, array given'
            ],
        ];
    }

    /**
     * @dataProvider validArgumentProvider
     */
    public function testValidate($entityData, $addViolation, $sourceEntity)
    {
        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->$addViolation())
            ->method('addViolation');

        $constraint = $this
            ->getMock('Oro\Bundle\EntityMergeBundle\Validator\Constraints\SourceEntity');
        $this->validator->initialize($context);

        $entityData
            ->expects($this->any())
            ->method('getFields')
            ->will(
                $this->returnValue(
                    [
                        $this->createFieldData($sourceEntity)
                    ]
                )
            );

        $this->validator->validate($entityData, $constraint);
    }

    public function validArgumentProvider()
    {
        return [
            'valid'     => [
                'entityData'   => $this->createEntityData(),
                'addViolation' => 'never',
                'sourceEntity' => new EntityStub('entity-0'),
            ],
            'non-valid' => [
                'entityData'   => $this->createEntityData(),
                'addViolation' => 'once',
                'sourceEntity' => new EntityStub('non-valid'),
            ],
        ];
    }

    /**
     * @return object
     */
    private function createEntityData()
    {
        $entityData = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();

        $entityData
            ->expects($this->any())
            ->method('getEntities')
            ->will(
                $this->returnValue(
                    [
                        new EntityStub('entity-0'),
                        new EntityStub('entity-1'),
                    ]
                )
            );

        return $entityData;
    }

    protected function createFieldData($sourceEntity)
    {
        $fieldData = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldData')
            ->setMethods(['getSourceEntity', 'getFieldName'])
            ->disableOriginalConstructor()
            ->getMock();

        $fieldData->expects($this->any())
            ->method('getSourceEntity')
            ->will($this->returnValue($sourceEntity));

        $fieldData->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue('field-' . $sourceEntity->getId()));

        return $fieldData;
    }
}
