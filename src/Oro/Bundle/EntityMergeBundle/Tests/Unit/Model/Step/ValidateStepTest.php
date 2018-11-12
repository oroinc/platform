<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Step;

use Oro\Bundle\EntityMergeBundle\Model\Step\ValidateStep;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidateStepTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ValidateStep
     */
    protected $step;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $validator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $constraintViolation;

    protected function setUp()
    {
        $this->validator = $this
            ->createMock(ValidatorInterface::class);

        $this->constraintViolation = $this
            ->getMockBuilder('Symfony\Component\Validator\ConstraintViolationList')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->will($this->returnValue($this->constraintViolation));

        $this->step = new ValidateStep($this->validator);
    }

    public function testRun()
    {
        $data = $this->createEntityData();

        $this->step->run($data);
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\ValidationException
     */
    public function testFail()
    {
        $data = $this->createEntityData();

        $this->constraintViolation
            ->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1));

        $this->step->run($data);
    }

    protected function createEntityData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createTestEntity($id)
    {
        $result     = new \stdClass();
        $result->id = $id;
        return $result;
    }
}
