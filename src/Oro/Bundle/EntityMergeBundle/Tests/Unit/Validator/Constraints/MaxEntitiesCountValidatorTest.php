<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MaxEntitiesCountValidator;

class MaxEntitiesCountValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MaxEntitiesCountValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->validator = new MaxEntitiesCountValidator();
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
            ->getMock('Oro\Bundle\EntityMergeBundle\Validator\Constraints\MaxEntitiesCount');
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
                'value'        => $this->createEntityData(5, 2),
                'addViolation' => 'never'
            ],
            'valid-custom'  => [
                'value'        => $this->createEntityData(10, 2),
                'addViolation' => 'never'
            ],
            'non-valid'     => [
                'value'        => $this->createEntityData(5, 10),
                'addViolation' => 'once'
            ],
        ];
    }

    private function createEntityData($maxCount, $count)
    {
        $entityData = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata
            ->expects($this->any())
            ->method('getMaxEntitiesCount')
            ->will($this->returnValue($maxCount));

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
