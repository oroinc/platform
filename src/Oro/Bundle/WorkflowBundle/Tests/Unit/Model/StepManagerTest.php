<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;

use Oro\Component\Testing\Unit\EntityTrait;

class StepManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testGetOrderedSteps()
    {
        $defaultStartStep = $this->getStep(StepManager::DEFAULT_START_STEP_NAME, -1);
        $stepOne = $this->getStep('step1', 1);
        $stepTwo = $this->getStep('step2', 2);
        $stepThree = $this->getStep('step3', 3);

        $stepManager = new StepManager(new ArrayCollection([$stepTwo, $stepOne, $stepThree, $defaultStartStep]));

        $ordered = $stepManager->getOrderedSteps();

        $this->assertInstanceOf(ArrayCollection::class, $ordered);
        $this->assertCount(4, $ordered);
        $this->assertSame($defaultStartStep, $ordered->get(0), 'Steps are not in correct order');
        $this->assertSame($stepOne, $ordered->get(1), 'Steps are not in correct order');
        $this->assertSame($stepTwo, $ordered->get(2), 'Steps are not in correct order');
        $this->assertSame($stepThree, $ordered->get(3), 'Steps are not in correct order');

        $ordered = $stepManager->getOrderedSteps(true);

        $this->assertCount(3, $ordered);
        $this->assertFalse($ordered->contains($defaultStartStep));
    }

    public function testGetStepsEmpty()
    {
        $stepManager = new StepManager();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $stepManager->getSteps());
    }

    public function testSetSteps()
    {
        $stepOne = $this->getStep('step1');
        $stepTwo = $this->getStep('step2');

        $stepManager = new StepManager();
        $stepManager->setSteps([$stepOne, $stepTwo]);
        $steps = $stepManager->getSteps();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $steps);
        $expected = ['step1' => $stepOne, 'step2' => $stepTwo];
        $this->assertEquals($expected, $steps->toArray());

        $stepsCollection = new ArrayCollection(['step1' => $stepOne, 'step2' => $stepTwo]);
        $stepManager->setSteps($stepsCollection);
        $steps = $stepManager->getSteps();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $steps);
        $expected = ['step1' => $stepOne, 'step2' => $stepTwo];
        $this->assertEquals($expected, $steps->toArray());
    }

    public function testGetStep()
    {
        $step1 = $this->getStep('step1');
        $step2 = $this->getStep('step2');

        $steps = new ArrayCollection([$step1, $step2]);
        $stepManager = new StepManager($steps);

        $this->assertEquals($step1, $stepManager->getStep('step1'));
        $this->assertEquals($step2, $stepManager->getStep('step2'));
    }

    /**
     * @param string $name
     * @param null|int $order
     * @return Step
     */
    protected function getStep($name, $order = null)
    {
        return $this->getEntity(Step::class, ['name' => $name, 'order' => $order]);
    }

    public function testStartStep()
    {
        $testStartStep = 'start_step';

        $startStep = new Step();
        $startStep->setName($testStartStep);

        $stepManager = new StepManager([$startStep]);
        $this->assertNull($stepManager->getStartStep());
        $this->assertFalse($stepManager->hasStartStep());

        $stepManager->setStartStepName($testStartStep);
        $this->assertEquals($startStep, $stepManager->getStartStep());
        $this->assertTrue($stepManager->hasStartStep());
    }

    public function testGetRelatedTransitionSteps()
    {
        $step1 = new Step();
        $step1->setName('step1');
        $step1->setAllowedTransitions(['transitionA']);
        $step2 = new Step();
        $step2->setName('step2');
        $step2->setAllowedTransitions(['transitionC', 'transitionA']);
        $step3 = new Step();
        $step3->setName('step3');
        $step3->setAllowedTransitions(['transitionC']);

        $stepManager = new StepManager([$step1, $step2, $step3]);

        $steps = $stepManager->getRelatedTransitionSteps('transitionA');

        $this->assertEquals([$step1, $step2], $steps->getValues());
    }

    public function testGetDefaultStartTransition()
    {
        $stepManager = new StepManager();
        $this->assertNull($stepManager->getDefaultStartStep());

        $step = $this->getStep(StepManager::DEFAULT_START_STEP_NAME);

        $stepManager->setSteps([$step]);
        $this->assertEquals($step, $stepManager->getDefaultStartStep());
    }
}
