<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;

class TransitionManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransitionManager */
    protected $transitionManager;

    protected function setUp()
    {
        $this->transitionManager = new TransitionManager();
    }

    public function testGetTransitionsEmpty()
    {
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->transitionManager->getTransitions()
        );
    }

    public function testGetTransition()
    {
        $transition = $this->getTransitionMock('transition');

        $this->transitionManager->setTransitions([$transition]);

        $this->assertEquals($transition, $this->transitionManager->getTransition('transition'));
    }

    /**
     * @dataProvider getStartTransitionDataProvider
     *
     * @param string $name
     * @param array $transitions
     * @param Transition|null $expected
     */
    public function testGetStartTransition($name, array $transitions, Transition $expected = null)
    {
        $this->transitionManager->setTransitions($transitions);

        $this->assertEquals($expected, $this->transitionManager->getStartTransition($name));
    }

    /**
     * return \Generator
     */
    public function getStartTransitionDataProvider()
    {
        $transition = $this->getTransitionMock('test_transition');
        $startTransition = $this->getTransitionMock('test_start_transition', true);
        $defaultStartTransition = $this->getTransitionMock(TransitionManager::DEFAULT_START_TRANSITION_NAME, true);

        yield 'invalid name' => [
            'name' => 10,
            'transitions' => [$transition, $startTransition, $defaultStartTransition],
            'expected' => null
        ];

        yield 'empty name' => [
            'name' => '',
            'transitions' => [$transition, $startTransition],
            'expected' => null
        ];

        yield 'empty name with default transition' => [
            'name' => '',
            'transitions' => [$transition, $startTransition, $defaultStartTransition],
            'expected' => $defaultStartTransition
        ];

        yield 'invalid string name' => [
            'name' => 'invalid_transition_name',
            'transitions' => [$transition, $startTransition, $defaultStartTransition],
            'expected' => $defaultStartTransition
        ];

        yield 'string name and not start transition' => [
            'name' => 'test_transition',
            'transitions' => [$transition, $startTransition, $defaultStartTransition],
            'expected' => null
        ];

        yield 'string name and start transition' => [
            'name' => 'test_start_transition',
            'transitions' => [$transition, $startTransition, $defaultStartTransition],
            'expected' => $startTransition
        ];

        yield 'string name and start transition' => [
            'name' => 'test_start_transition',
            'transitions' => [$transition, $startTransition, $defaultStartTransition],
            'expected' => $startTransition
        ];

        yield 'string name and start transition' => [
            'name' => 'test_start_transition',
            'transitions' => [$transition, $startTransition, $defaultStartTransition],
            'expected' => $startTransition
        ];
    }

    public function testSetTransitions()
    {
        $transitionOne = $this->getTransitionMock('transition1');
        $transitionTwo = $this->getTransitionMock('transition2');

        $this->transitionManager->setTransitions([$transitionOne, $transitionTwo]);
        $transitions = $this->transitionManager->getTransitions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $transitions);
        $expected = ['transition1' => $transitionOne, 'transition2' => $transitionTwo];
        $this->assertEquals($expected, $transitions->toArray());

        $transitionsCollection = new ArrayCollection(
            ['transition1' => $transitionOne, 'transition2' => $transitionTwo]
        );
        $this->transitionManager->setTransitions($transitionsCollection);
        $transitions = $this->transitionManager->getTransitions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $transitions);
        $expected = ['transition1' => $transitionOne, 'transition2' => $transitionTwo];
        $this->assertEquals($expected, $transitions->toArray());
    }

    /**
     * @param string $name
     * @param bool $isStart
     * @param Step $step
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getTransitionMock($name, $isStart = false, $step = null)
    {
        $transition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Transition')
            ->disableOriginalConstructor()
            ->getMock();
        $transition->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        if ($isStart) {
            $transition->expects($this->any())
                ->method('isStart')
                ->will($this->returnValue($isStart));
        }
        if ($step) {
            $transition->expects($this->any())
                ->method('getStepTo')
                ->will($this->returnValue($step));
        }

        return $transition;
    }

    public function testGetStartTransitions()
    {
        $allowedStartTransition = $this->getTransitionMock('test_start', true);
        $allowedTransition = $this->getTransitionMock('test', false);

        $transitions = new ArrayCollection(
            [
                $allowedStartTransition,
                $allowedTransition
            ]
        );
        $expected = new ArrayCollection(['test_start' => $allowedStartTransition]);

        $this->transitionManager->setTransitions($transitions);
        $this->assertEquals($expected, $this->transitionManager->getStartTransitions());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected transition argument type is string or Transition, but stdClass given
     */
    public function testExtractTransitionException()
    {
        $transition = new \stdClass();
        $this->transitionManager->extractTransition($transition);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException
     * @expectedExceptionMessage Transition "test" is not exist in workflow.
     */
    public function testExtractTransitionStringUnknown()
    {
        $transition = 'test';
        $this->transitionManager->extractTransition($transition);
    }

    public function testExtractTransition()
    {
        $transition = $this->getTransitionMock('test');
        $this->assertSame($transition, $this->transitionManager->extractTransition($transition));
    }

    public function testExtractTransitionString()
    {
        $transitionName = 'test';
        $transition = $this->getTransitionMock($transitionName);
        $this->transitionManager->setTransitions([$transition]);

        $this->assertSame($transition, $this->transitionManager->extractTransition($transitionName));
    }

    public function testGetDefaultStartTransition()
    {
        $this->assertNull($this->transitionManager->getDefaultStartTransition());

        $transition = $this->getTransitionMock(TransitionManager::DEFAULT_START_TRANSITION_NAME);

        $this->transitionManager->setTransitions(new ArrayCollection([$transition]));
        $this->assertEquals($transition, $this->transitionManager->getDefaultStartTransition());
    }
}
