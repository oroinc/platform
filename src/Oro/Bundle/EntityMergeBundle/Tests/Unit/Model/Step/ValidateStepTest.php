<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Step;

use Oro\Bundle\EntityMergeBundle\Model\Step\ValidateStep;

class ValidateStepTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ValidateStep
     */
    protected $step;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $validator;

    protected function setUp()
    {
        $this->validator = $this->getMock('Symfony\Component\Validator\ValidatorInterface');
        $this->step = new ValidateStep($this->validator);
    }

    public function testRun()
    {
        $fooEntity = $this->createTestEntity(1);
        $barEntity = $this->createTestEntity(2);

        $data = $this->createEntityData();

        $data->expects($this->once())
            ->method('getEntities')
            ->will($this->returnValue(array($fooEntity, $barEntity)));

        $data->expects($this->once())
            ->method('getMasterEntity')
            ->will($this->returnValue($fooEntity));

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
