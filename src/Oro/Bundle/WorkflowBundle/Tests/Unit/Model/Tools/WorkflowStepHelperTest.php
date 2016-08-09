<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Tools;

use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Tools\WorkflowStepHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

use Oro\Component\Testing\Unit\EntityTrait;

class WorkflowStepHelperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var array */
    static protected $steps;

    /**
     * @dataProvider getStepsAfterDataProvider
     *
     * @param Step $step
     * @param array $expected
     */
    public function testGetStepsAfter(Step $step, array $expected)
    {
        $helper = new WorkflowStepHelper($this->getWorkflowMock());

        $this->assertEquals($expected, $helper->getStepsAfter($step));
    }

    /**
     * @return array
     */
    public function getStepsAfterDataProvider()
    {
        return [
            [
                'step' => $this->getStepByNumber(1),
                'expected' => [
                    $this->getStepByNumber(2)
                ]
            ],
            [
                'step' => $this->getStepByNumber(2),
                'expected' => [
                    $this->getStepByNumber(4),
                    $this->getStepByNumber(5)
                ]
            ],
            [
                'step' => $this->getStepByNumber(3),
                'expected' => [
                    $this->getStepByNumber(4)
                ]
            ],
            [
                'step' => $this->getStepByNumber(4),
                'expected' => []
            ],
            [
                'step' => $this->getStepByNumber(5),
                'expected' => []
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Workflow
     */
    protected function getWorkflowMock()
    {
        $transitionManager = new TransitionManager(
            [
                $this->getTransition('transition1', $this->getStepByNumber(1)),
                $this->getTransition('transition2', $this->getStepByNumber(2)),
                $this->getTransition('transition3', $this->getStepByNumber(3)),
                $this->getTransition('transition4', $this->getStepByNumber(4)),
                $this->getTransition('transition5', $this->getStepByNumber(5))
            ]
        );
        $stepManager = new StepManager(
            [
                $this->getStepByNumber(1),
                $this->getStepByNumber(2),
                $this->getStepByNumber(3),
                $this->getStepByNumber(4),
                $this->getStepByNumber(5)
            ]
        );

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->any())->method('getTransitionManager')->willReturn($transitionManager);
        $workflow->expects($this->any())->method('getStepManager')->willReturn($stepManager);

        return $workflow;
    }

    /**
     * @param string $name
     * @param Step $stepTo
     * @return Transition
     */
    protected function getTransition($name, Step $stepTo)
    {
        return $this->getEntity(
            Transition::class,
            [
                'name' => $name,
                'label' => $name . 'Label',
                'stepTo' => $stepTo
            ]
        );
    }

    /**
     * @param string $name
     * @param int $order
     * @param array $allowedTransitions
     * @return Step
     */
    protected function getStep($name, $order, array $allowedTransitions)
    {
        return $this->getEntity(
            Step::class,
            [
                'name' => $name,
                'label' => $name . 'Label',
                'order' => $order,
                'allowedTransitions' => $allowedTransitions
            ]
        );
    }

    /**
     * @param int $number
     * @return Step
     */
    protected function getStepByNumber($number)
    {
        if (!self::$steps) {
            self::$steps = [
                1 => $this->getStep('step1', 10, ['transition2']),
                2 => $this->getStep('step2', 20, ['transition1', 'transition3', 'transition4', 'transition5']),
                3 => $this->getStep('step3', 20, ['transition1', 'transition4']),
                4 => $this->getStep('step4', 30, ['transition1', 'transition2', 'transition3']),
                5 => $this->getStep('step5', 40, ['transition2'])
            ];
        }

        return self::$steps[$number];
    }
}
