<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MinEntitiesCountValidator;

class MinEntitiesCountValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MinEntitiesCountValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->validator = new MinEntitiesCountValidator();
    }

    /**
     * @dataProvider invalidArgumentProvider
     * @param mixed $value
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
            ->getMock('Oro\Bundle\EntityMergeBundle\Validator\Constraints\MaxEntitiesCount');
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
     * @param mixed $value
     * @param string $addViolation
     */
    public function testValidate($value, $addViolation)
    {
        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->$addViolation())
            ->method('addViolation');

        $constraint = $this
            ->getMock('Oro\Bundle\EntityMergeBundle\Validator\Constraints\MaxEntitiesCount');
        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    public function validArgumentProvider()
    {
        return [
            'valid-default' => [
                'value'        => $this->createEntityData(5),
                'addViolation' => 'never'
            ],
            'valid-custom'  => [
                'value'        => $this->createEntityData(10),
                'addViolation' => 'never'
            ],
            'non-valid'     => [
                'value'        => $this->createEntityData(1),
                'addViolation' => 'once'
            ],
        ];
    }

    /**
     * @param integer $maxCount
     * @return mixed
     */
    private function createEntityData($count)
    {
        $entityData = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $entityData
            ->expects($this->any())
            ->method('getMetadata')
            ->will($this->returnValue($metadata));

        $entityData
            ->expects($this->any())
            ->method('getEntities')
            ->will($this->returnValue(array_fill(0, $count, new \stdClass())));

        return $entityData;
    }
}
