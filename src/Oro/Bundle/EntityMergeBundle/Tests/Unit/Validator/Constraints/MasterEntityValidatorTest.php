<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MasterEntityValidator;

class MasterEntityValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MasterEntityValidator
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
            ->will($this->returnCallback(
                    function ($entity, $other) {
                        return $entity->getId() === $other->getId();
                    }
                ));

        $this->validator = new MasterEntityValidator($doctrineHelper);
    }

    /**
     * @dataProvider invalidArgumentProvider
     * @param mixed  $value
     * @param string $exception
     * @param string $expectedExceptionMessage
     */
    public function testInvalidArgument($value, $exception, $expectedExceptionMessage)
    {
        $this->setExpectedException(
            $exception,
            $expectedExceptionMessage
        );

        $constraint = $this
            ->getMock('Oro\Bundle\EntityMergeBundle\Validator\Constraints\MasterEntity');
        $this->validator->validate($value, $constraint);
    }

    public function invalidArgumentProvider()
    {
        return [
            'bool'    => [
                'value'                    => true,
                'exception'                =>
                    'Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException',
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, boolean given'
            ],
            'string'  => [
                'value'                    => 'string',
                'exception'                =>
                    'Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException',
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, string given'
            ],
            'integer' => [
                'value'                    => 5,
                'exception'                =>
                    'Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException',
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, integer given'
            ],
            'null'    => [
                'value'                    => null,
                'exception'                =>
                    'Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException',
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, NULL given'
            ],
            'object'  => [
                'value'                    => new \stdClass(),
                'exception'                =>
                    'Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException',
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, stdClass given'
            ],
            'array'   => [
                'value'                    => [],
                'exception'                =>
                    'Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException',
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, array given'
            ],
        ];
    }

    /**
     * @dataProvider validArgumentProvider
     *
     * @param object $value
     * @param string $addViolation
     * @param object $masterEntity
     */
    public function testValidate($value, $addViolation, $masterEntity)
    {
        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->$addViolation())
            ->method('addViolation');

        $constraint = $this
            ->getMock('Oro\Bundle\EntityMergeBundle\Validator\Constraints\MasterEntity');
        $this->validator->initialize($context);

        $value
            ->expects($this->any())
            ->method('getMasterEntity')
            ->will($this->returnValue($masterEntity));

        $this->validator->validate($value, $constraint);
    }

    public function validArgumentProvider()
    {
        return [
            'valid' => [
                'value'        => $this->createEntityData(),
                'addViolation' => 'never',
                'masterEntity' => new EntityStub('entity'),
            ],
            'non-valid'     => [
                'value'        => $this->createEntityData(),
                'addViolation' => 'once',
                'masterEntity' => new EntityStub('non-valid'),
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
                        new EntityStub('entity'),
                        new EntityStub('entity-2'),
                    ]
                )
            );

        return $entityData;
    }
}
