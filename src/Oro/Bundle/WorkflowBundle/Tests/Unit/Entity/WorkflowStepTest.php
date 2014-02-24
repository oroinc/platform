<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Symfony\Component\PropertyAccess\PropertyAccess;

class WorkflowStepTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkflowStep
     */
    protected $step;

    protected function setUp()
    {
        $this->step = new WorkflowStep();
    }

    protected function tearDown()
    {
        unset($this->step);
    }

    public function testGetId()
    {
        $this->assertNull($this->step->getId());

        $value = 42;
        $idReflection = new \ReflectionProperty('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep', 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($this->step, $value);
        $this->assertEquals($value, $this->step->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($this->step, $property, $value);
        $this->assertEquals($value, $accessor->getValue($this->step, $property));
    }

    public function propertiesDataProvider()
    {
        return array(
            array('name', 'test'),
            array('label', 'test'),
            array('stepOrder', 1),
            array('definition', $this->getMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')),
            array('final', true)
        );
    }

    public function testImport()
    {
        $this->step->setName('test');
        $this->step->setLabel('test');
        $this->step->setStepOrder(1);
        $this->step->setFinal(true);

        $newStep = new WorkflowStep();
        $newStep->import($this->step);

        $this->assertEquals('test', $newStep->getName());
        $this->assertEquals('test', $newStep->getLabel());
        $this->assertEquals(1, $newStep->getStepOrder());
        $this->assertTrue($newStep->isFinal());
    }
}
