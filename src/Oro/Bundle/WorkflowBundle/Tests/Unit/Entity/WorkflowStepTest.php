<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Symfony\Component\PropertyAccess\PropertyAccess;

class WorkflowStepTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new WorkflowStep();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        return array(
            array('name', 'test'),
            array('label', 'test'),
            array('stepOrder', 1),
            array('definition', $this->getMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition'))
        );
    }

    public function testImport()
    {
        $step = new WorkflowStep();
        $step->setName('test');
        $step->setLabel('test');
        $step->setStepOrder(1);

        $newStep = new WorkflowStep();
        $newStep->import($step);

        $this->assertEquals('test', $newStep->getName());
        $this->assertEquals('test', $newStep->getLabel());
        $this->assertEquals(1, $newStep->getStepOrder());
    }
}
