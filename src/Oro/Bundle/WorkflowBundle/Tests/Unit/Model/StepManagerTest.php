<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;

class StepManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOrderedSteps()
    {
        $stepOne = new Step();
        $stepOne->setName('step1');
        $stepOne->setOrder(1);

        $stepTwo = new Step();
        $stepTwo->setName('step2');
        $stepTwo->setOrder(2);

        $stepThree = new Step();
        $stepThree->setName('step3');
        $stepThree->setOrder(3);
        $steps = new ArrayCollection(array($stepTwo, $stepOne, $stepThree));

        $stepManager = new StepManager($steps);
        $ordered = $stepManager->getOrderedSteps();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $ordered);
        $this->assertSame($stepOne, $ordered->get(0), 'Steps are not in correct order');
        $this->assertSame($stepTwo, $ordered->get(1), 'Steps are not in correct order');
        $this->assertSame($stepThree, $ordered->get(2), 'Steps are not in correct order');
    }

    public function testGetStepsEmpty()
    {
        $stepManager = new StepManager();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $stepManager->getSteps());
    }

    public function testSetSteps()
    {
        $stepOne = $this->getStepMock('step1');
        $stepTwo = $this->getStepMock('step2');

        $stepManager = new StepManager();
        $stepManager->setSteps(array($stepOne, $stepTwo));
        $steps = $stepManager->getSteps();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $steps);
        $expected = array('step1' => $stepOne, 'step2' => $stepTwo);
        $this->assertEquals($expected, $steps->toArray());

        $stepsCollection = new ArrayCollection(array('step1' => $stepOne, 'step2' => $stepTwo));
        $stepManager->setSteps($stepsCollection);
        $steps = $stepManager->getSteps();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $steps);
        $expected = array('step1' => $stepOne, 'step2' => $stepTwo);
        $this->assertEquals($expected, $steps->toArray());
    }

    public function testGetStep()
    {
        $step1 = $this->getStepMock('step1');
        $step2 = $this->getStepMock('step2');

        $steps = new ArrayCollection(array($step1, $step2));
        $stepManager = new StepManager($steps);

        $this->assertEquals($step1, $stepManager->getStep('step1'));
        $this->assertEquals($step2, $stepManager->getStep('step2'));
    }

    protected function getStepMock($name)
    {
        $step = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Step')
            ->disableOriginalConstructor()
            ->getMock();
        $step->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $step;
    }
}
