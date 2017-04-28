<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;

class TransitionManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTransitionsEmpty()
    {
        $transitionsManager = new TransitionManager();
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $transitionsManager->getTransitions()
        );
    }

    public function testGetTransition()
    {
        $transition = $this->getTransitionMock('transition');

        $transitionsManager = new TransitionManager();
        $transitionsManager->setTransitions(array($transition));

        $this->assertEquals($transition, $transitionsManager->getTransition('transition'));
    }

    public function testSetTransitions()
    {
        $transitionOne = $this->getTransitionMock('transition1');
        $transitionTwo = $this->getTransitionMock('transition2');

        $transitionsManager = new TransitionManager();

        $transitionsManager->setTransitions(array($transitionOne, $transitionTwo));
        $transitions = $transitionsManager->getTransitions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $transitions);
        $expected = array('transition1' => $transitionOne, 'transition2' => $transitionTwo);
        $this->assertEquals($expected, $transitions->toArray());

        $transitionsCollection = new ArrayCollection(
            array('transition1' => $transitionOne, 'transition2' => $transitionTwo)
        );
        $transitionsManager->setTransitions($transitionsCollection);
        $transitions = $transitionsManager->getTransitions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $transitions);
        $expected = array('transition1' => $transitionOne, 'transition2' => $transitionTwo);
        $this->assertEquals($expected, $transitions->toArray());
    }

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
            array(
                $allowedStartTransition,
                $allowedTransition
            )
        );
        $expected = new ArrayCollection(array('test_start' => $allowedStartTransition));

        $transitionsManager = new TransitionManager();
        $transitionsManager->setTransitions($transitions);
        $this->assertEquals($expected, $transitionsManager->getStartTransitions());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected transition argument type is string or Transition, but stdClass given
     */
    public function testExtractTransitionException()
    {
        $transition = new \stdClass();
        $transitionsManager = new TransitionManager();
        $transitionsManager->extractTransition($transition);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException
     * @expectedExceptionMessage Transition "test" is not exist in workflow.
     */
    public function testExtractTransitionStringUnknown()
    {
        $transition = 'test';
        $transitionsManager = new TransitionManager();
        $transitionsManager->extractTransition($transition);
    }

    public function testExtractTransition()
    {
        $transition = $this->getTransitionMock('test');
        $transitionsManager = new TransitionManager();
        $this->assertSame($transition, $transitionsManager->extractTransition($transition));
    }

    public function testExtractTransitionString()
    {
        $transitionName = 'test';
        $transition = $this->getTransitionMock($transitionName);
        $transitionsManager = new TransitionManager(new ArrayCollection(array($transition)));

        $this->assertSame($transition, $transitionsManager->extractTransition($transitionName));
    }

    public function testGetDefaultStartTransition()
    {
        $transitionsManager = new TransitionManager();
        $this->assertNull($transitionsManager->getDefaultStartTransition());

        $transition = $this->getTransitionMock(TransitionManager::DEFAULT_START_TRANSITION_NAME);

        $transitionsManager->setTransitions(new ArrayCollection(array($transition)));
        $this->assertEquals($transition, $transitionsManager->getDefaultStartTransition());
    }
}
