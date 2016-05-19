<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\Transition;

class TransitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testGettersAndSetters($property, $value)
    {
        $ucProp = ucfirst($property);
        $setter = 'set' . $ucProp;
        $obj = new Transition();
        $getter = 'get' . $ucProp;
        if (!method_exists($obj, $getter)) {
            $getter = 'is' . $ucProp;
        }
        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Transition',
            call_user_func_array(array($obj, $setter), array($value))
        );
        $this->assertEquals($value, call_user_func_array(array($obj, $getter), array()));
    }

    public function propertiesDataProvider()
    {
        return array(
            'name' => array('name', 'test'),
            'label' => array('label', 'test'),
            'message' => array('message', 'test'),
            'hidden' => array('hidden', true),
            'start' => array('start', true),
            'unavailableHidden' => array('unavailableHidden', true),
            'stepTo' => array('stepTo', $this->getStepMock('testStep')),
            'frontendOptions' => array('frontendOptions', array('key' => 'value')),
            'form_type' => array('formType', 'custom_workflow_transition'),
            'display_type' => array('displayType', 'page'),
            'form_options' => array('formOptions', array('one', 'two')),
            'page_template' => array('pageTemplate', 'Workflow:Test:page_template.html.twig'),
            'dialog_template' => array('dialogTemplate', 'Workflow:Test:dialog_template.html.twig'),
            'pre_condition' => array(
                'preCondition',
                $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface')
            ),
            'condition' => array(
                'condition',
                $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface')
            ),
            'postAction' => array(
                'postAction',
                $this->getMock('Oro\Component\Action\Action\ActionInterface')
            )
        );
    }

    public function testHidden()
    {
        $transition = new Transition();
        $this->assertFalse($transition->isHidden());
        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Transition',
            $transition->setHidden(true)
        );
        $this->assertTrue($transition->isHidden());
        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Transition',
            $transition->setHidden(false)
        );
        $this->assertFalse($transition->isHidden());
    }

    /**
     * @dataProvider isAllowedDataProvider
     * @param bool $isAllowed
     * @param bool $expected
     */
    public function testIsAllowed($isAllowed, $expected)
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();

        $obj = new Transition();

        if (null !== $isAllowed) {
            $condition = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
            $condition->expects($this->once())
                ->method('evaluate')
                ->with($workflowItem)
                ->will($this->returnValue($isAllowed));
            $obj->setCondition($condition);
        }

        $this->assertEquals($expected, $obj->isAllowed($workflowItem));
    }

    public function isAllowedDataProvider()
    {
        return array(
            'allowed' => array(
                'isAllowed' => true,
                'expected'  => true
            ),
            'not allowed' => array(
                'isAllowed' => false,
                'expected'  => false,
            ),
            'no condition' => array(
                'isAllowed' => null,
                'expected'  => true,
            ),
        );
    }

    /**
     * @dataProvider isAllowedDataProvider
     * @param bool $isAllowed
     * @param bool $expected
     */
    public function testIsAvailableWithForm($isAllowed, $expected)
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();

        $obj = new Transition();
        $obj->setFormOptions(array('key' => 'value'));

        if (null !== $isAllowed) {
            $condition = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
            $condition->expects($this->once())
                ->method('evaluate')
                ->with($workflowItem)
                ->will($this->returnValue($isAllowed));
            $obj->setPreCondition($condition);
        }

        $this->assertEquals($expected, $obj->isAvailable($workflowItem));
    }

    /**
     * @dataProvider isAvailableDataProvider
     * @param bool $isAllowed
     * @param bool $isAvailable
     * @param bool $expected
     */
    public function testIsAvailableWithoutForm($isAllowed, $isAvailable, $expected)
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();

        $obj = new Transition();

        if (null !== $isAvailable) {
            $preCondition = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
            $preCondition->expects($this->any())
                ->method('evaluate')
                ->with($workflowItem)
                ->will($this->returnValue($isAvailable));
            $obj->setPreCondition($preCondition);
        }
        if (null !== $isAllowed) {
            $condition = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
            $condition->expects($this->any())
                ->method('evaluate')
                ->with($workflowItem)
                ->will($this->returnValue($isAllowed));
            $obj->setCondition($condition);
        }

        $this->assertEquals($expected, $obj->isAvailable($workflowItem));
    }

    public function isAvailableDataProvider()
    {
        return array(
            'allowed' => array(
                'isAllowed' => true,
                'isAvailable' => true,
                'expected'  => true
            ),
            'not allowed #1' => array(
                'isAllowed' => false,
                'isAvailable' => true,
                'expected'  => false,
            ),
            'not allowed #2' => array(
                'isAllowed' => true,
                'isAvailable' => false,
                'expected'  => false,
            ),
            'not allowed #3' => array(
                'isAllowed' => false,
                'isAvailable' => false,
                'expected'  => false,
            ),
            'no conditions' => array(
                'isAllowed' => null,
                'isAvailable' => null,
                'expected'  => true,
            ),
        );
    }

    /**
     * @dataProvider transitDisallowedDataProvider
     * @param bool $preConditionAllowed
     * @param bool $conditionAllowed
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException
     * @expectedExceptionMessage Transition "test" is not allowed.
     */
    public function testTransitNotAllowed($preConditionAllowed, $conditionAllowed)
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->never())
            ->method('setCurrentStep');

        $preCondition = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $preCondition->expects($this->any())
            ->method('evaluate')
            ->with($workflowItem)
            ->will($this->returnValue($preConditionAllowed));

        $condition = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $condition->expects($this->any())
            ->method('evaluate')
            ->with($workflowItem)
            ->will($this->returnValue($conditionAllowed));

        $postAction = $this->getMock('Oro\Component\Action\Action\ActionInterface');
        $postAction->expects($this->never())
            ->method('execute');

        $obj = new Transition();
        $obj->setName('test');
        $obj->setPreCondition($preCondition);
        $obj->setCondition($condition);
        $obj->setPostAction($postAction);
        $obj->transit($workflowItem);
    }

    public function transitDisallowedDataProvider()
    {
        return array(
            array(false, false),
            array(false, true),
            array(true, false)
        );
    }

    /**
     * @dataProvider transitDataProvider
     * @param boolean $isFinal
     * @param boolean $hasAllowedTransition
     */
    public function testTransit($isFinal, $hasAllowedTransition)
    {
        $currentStepEntity =  $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep')
            ->disableOriginalConstructor()
            ->getMock();

        $step = $this->getStepMock('currentStep', $isFinal, $hasAllowedTransition, $currentStepEntity);

        $workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowDefinition->expects($this->once())
            ->method('getStepByName')
            ->with($step->getName())
            ->will($this->returnValue($currentStepEntity));

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($workflowDefinition));
        $workflowItem->expects($this->once())
            ->method('setCurrentStep')
            ->with($currentStepEntity);

        $preCondition = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $preCondition->expects($this->once())
            ->method('evaluate')
            ->with($workflowItem)
            ->will($this->returnValue(true));

        $condition = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $condition->expects($this->once())
            ->method('evaluate')
            ->with($workflowItem)
            ->will($this->returnValue(true));

        $postAction = $this->getMock('Oro\Component\Action\Action\ActionInterface');
        $postAction->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $obj = new Transition();
        $obj->setPreCondition($preCondition);
        $obj->setCondition($condition);
        $obj->setPostAction($postAction);
        $obj->setStepTo($step);
        $obj->transit($workflowItem);
    }

    /**
     * @return array
     */
    public function transitDataProvider()
    {
        return array(
            array(true, true),
            array(true, false),
            array(false, false)
        );
    }

    protected function getStepMock($name, $isFinal = false, $hasAllowedTransitions = true, $stepEntity = null)
    {
        $step = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Step')
            ->disableOriginalConstructor()
            ->getMock();
        $step->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $step->expects($this->any())
            ->method('isFinal')
            ->will($this->returnValue($isFinal));
        $step->expects($this->any())
            ->method('hasAllowedTransitions')
            ->will($this->returnValue($hasAllowedTransitions));
        return $step;
    }

    public function testStart()
    {
        $obj = new Transition();
        $this->assertFalse($obj->isStart());
        $obj->setStart(true);
        $this->assertTrue($obj->isStart());
    }

    public function testGetSetFrontendOption()
    {
        $obj = new Transition();

        $this->assertEquals(array(), $obj->getFrontendOptions());

        $frontendOptions = array('class' => 'foo', 'icon' => 'bar');
        $obj->setFrontendOptions($frontendOptions);
        $this->assertEquals($frontendOptions, $obj->getFrontendOptions());
    }

    public function testHasForm()
    {
        $obj = new Transition();

        $this->assertFalse($obj->hasForm()); // by default transition has form

        $obj->setFormOptions(array('key' => 'value'));
        $this->assertFalse($obj->hasForm());

        $obj->setFormOptions(array('attribute_fields' => array()));
        $this->assertFalse($obj->hasForm());

        $obj->setFormOptions(array('attribute_fields' => array('key' => 'value')));
        $this->assertTrue($obj->hasForm());
    }
}
