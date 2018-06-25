<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldData;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\UniqueEntity;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

class UniqueEntityValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UniqueEntityValidator
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
            ->method('getEntityIdentifierValue')
            ->will(
                $this->returnCallback(
                    function ($entity) {
                        return $entity->getId();
                    }
                )
            );

        $this->validator = new UniqueEntityValidator($doctrineHelper);
    }

    /**
     * @dataProvider invalidArgumentProvider
     */
    public function testInvalidArgument($value, $expectedExceptionMessage)
    {
        $this->expectException('Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException');
        $this->expectExceptionMessage($expectedExceptionMessage);

        $constraint = $this
            ->createMock('Oro\Bundle\EntityMergeBundle\Validator\Constraints\UniqueEntity');
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
    public function testValidate($entityData, $addViolation)
    {
        $context = $this->createMock(ExecutionContext::class);

        $context->expects($this->$addViolation())
            ->method('addViolation');

        $constraint = $this->createMock(UniqueEntity::class);
        $this->validator->initialize($context);

        $this->validator->validate($entityData, $constraint);
    }

    public function validArgumentProvider()
    {
        return [
            'valid'     => [
                'entityData'   => $this->createEntityData(['entity-0', 'entity-1']),
                'addViolation' => 'never',
            ],
            'non-valid' => [
                'entityData'   => $this->createEntityData(['duplicate', 'duplicate']),
                'addViolation' => 'once',
            ],
        ];
    }

    /**
     * @return object
     */
    private function createEntityData($ids)
    {
        $entities = array_map(
            function ($id) {
                return new EntityStub($id);
            },
            $ids
        );

        $entityData = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();

        $entityData
            ->expects($this->any())
            ->method('getEntities')
            ->will($this->returnValue($entities));

        return $entityData;
    }
}
