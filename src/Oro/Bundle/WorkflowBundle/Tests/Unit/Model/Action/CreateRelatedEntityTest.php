<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\Action\CreateRelatedEntity;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class CreateRelatedEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var CreateRelatedEntity
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new CreateRelatedEntity($this->contextAccessor, $this->registry);
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
    public function testExecuteExceptionInterface()
    {
        $context = new \stdClass();
        $this->action->execute($context);
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\NotManageableEntityException
     */
    public function testExecuteExceptionNotManaged()
    {
        $relatedEntity = '\stdClass';
        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('getRelatedEntity')
            ->will($this->returnValue($relatedEntity));

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($definition));

        $this->registry->expects($this->once())
            ->method('getManagerForClass');
        $this->action->execute($workflowItem);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\ActionException
     * @expectedExceptionMessage Can't create related entity \stdClass.
     */
    public function testExecuteSaveException()
    {
        $relatedEntity = '\stdClass';
        $entity = new \stdClass();
        $entity->test = null;

        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('getRelatedEntity')
            ->will($this->returnValue($relatedEntity));

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $workflowItem->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($definition));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->getMock();
        $em->expects($this->once())
            ->method('persist')
            ->with($entity)
            ->will(
                $this->returnCallback(
                    function () {
                        throw new \Exception();
                    }
                )
            );

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));

        $this->action->initialize(array());
        $this->action->execute($workflowItem);
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     */
    public function testExecute($options)
    {
        $relatedEntity = '\stdClass';
        $entity = new \stdClass();
        $entity->test = null;

        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('getRelatedEntity')
            ->will($this->returnValue($relatedEntity));

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $workflowItem->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($definition));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->getMock();
        $em->expects($this->once())
            ->method('persist')
            ->with($entity);
        $em->expects($this->once())
            ->method('flush')
            ->with($entity);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));

        $this->action->initialize($options);
        $this->action->execute($workflowItem);
    }
}
