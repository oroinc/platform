<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\Action\CreateRelatedEntity;

class CreateRelatedEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $createEntityAction;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $replacePropertyPath;

    /**
     * @var CreateRelatedEntity
     */
    protected $action;

    protected function setUp()
    {
        $this->createEntityAction = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface')
            ->getMock();
        $this->replacePropertyPath = $this
            ->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ConfigurationPass\ConfigurationPassInterface')
            ->getMock();
        $this->action = new CreateRelatedEntity($this->createEntityAction, $this->replacePropertyPath);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Object data must be an array.
     */
    public function testInitializeException()
    {
        $options = array(
            'data' => 'test'
        );
        $this->action->initialize($options);
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     */
    public function testInitialize($options)
    {
        $this->assertSame($this->action, $this->action->initialize($options));
        $this->assertAttributeEquals($options, 'options', $this->action);
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return array(
            array(array()),
            array(array('data' => null)),
            array(array('data' => array('test' => 'data'))),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Context must be instance of WorkflowItem
     */
    public function testExecuteException()
    {
        $context = new \stdClass();
        $this->action->execute($context);
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     */
    public function testExecute($options)
    {
        $attributeName = 'test_attribute_name';
        $relatedEntity = '\stdClass';
        $entity = new \stdClass();

        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('getEntityAttributeName')
            ->will($this->returnValue($attributeName));
        $definition->expects($this->once())
            ->method('getRelatedEntity')
            ->will($this->returnValue($relatedEntity));

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($definition));
        $workflowItem->expects($this->once())
            ->method('setEntity')
            ->with($entity);

        $expectedOptions = array(
            'attribute' => '$' . $attributeName,
            'class' => $relatedEntity,
            'data' => array_key_exists('data', $options) ? $options['data'] : null
        );
        $passedOptions = $expectedOptions;
        $passedOptions['attribute'] = 'data.' . $attributeName;
        $this->replacePropertyPath->expects($this->once())
            ->method('passConfiguration')
            ->with($expectedOptions)
            ->will($this->returnValue($passedOptions));

        $this->createEntityAction->expects($this->once())
            ->method('initialize')
            ->with($passedOptions)
            ->will($this->returnSelf());
        $this->createEntityAction->expects($this->once())
            ->method('execute')
            ->with($workflowItem)
            ->will($this->returnValue($entity));

        $this->action->initialize($options);
        $this->action->execute($workflowItem);
    }
}
