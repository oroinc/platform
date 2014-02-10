<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\Proxy;
use Doctrine\ORM\Events;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowStepSubscriber;

class WorkflowStepSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityConnector;

    /**
     * @var WorkflowStepSubscriber
     */
    protected $subscriber;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConnector = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\EntityConnector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new WorkflowStepSubscriber($this->registry, $this->entityConnector);
    }

    public function testGetSubscribedEvents()
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertCount(1, $events);
        // @codingStandardsIgnoreStart
        $this->assertContains(Events::prePersist, $events);
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider stepDataProvider
     * @param \PHPUnit_Framework_MockObject_MockObject $step
     */
    public function testPrePersist($step)
    {
        $entity = new \stdClass();

        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        // isSupportStartStep()
        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('getRelatedEntity')
            ->will($this->returnValue(get_class($entity)));
        $definitions = array($definition);

        $repository = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findAllWithStartStep')
            ->will($this->returnValue($definitions));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroWorkflowBundle:WorkflowDefinition')
            ->will($this->returnValue($repository));

        // setStartStep()
        if ($step instanceof Proxy) {
            $step->expects($this->once())
                ->method('__isInitialized')
                ->will($this->returnValue(false));
            $step->expects($this->once())
                ->method('__load');
        }

        $this->entityConnector->expects($this->once())
            ->method('getWorkflowStep')
            ->with($entity);

        $definition->expects($this->once())
            ->method('getStartStep')
            ->will($this->returnValue($step));

        $this->entityConnector->expects($this->once())
            ->method('setWorkflowStep')
            ->with($entity, $step);

        $this->subscriber->prePersist($event);
    }

    /**
     * @return array
     */
    public function stepDataProvider()
    {
        return array(
            array(
                $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep')
                    ->disableOriginalConstructor()
                    ->getMock()
            ),
            array(
                $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Stubs\StepProxyStub')
                    ->getMock()
            )
        );
    }
}
