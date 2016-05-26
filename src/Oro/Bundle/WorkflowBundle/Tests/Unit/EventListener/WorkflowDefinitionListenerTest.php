<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionListener;
use Oro\Bundle\WorkflowBundle\Generator\ProcessConfigurationGenerator;
use Oro\Bundle\WorkflowBundle\Model\ProcessImport;

use Oro\Component\DependencyInjection\ServiceLink;

class WorkflowDefinitionListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessConfigurationGenerator */
    protected $generator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink */
    protected $importLink;

    /** @var WorkflowDefinitionListener */
    protected $listener;

    protected function setUp()
    {
        $this->generator = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Generator\ProcessConfigurationGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->importLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WorkflowDefinitionListener($this->generator, $this->importLink);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->generator, $this->importLink);
    }

    public function testPostPersist()
    {
        $entity = new WorkflowDefinition();

        $this->assertImportExecuted($entity, ['configuration']);

        $this->listener->postPersist($this->createEvent($entity));
    }

    public function testPostPersistWithUnsupportedEntity()
    {
        $this->assertImportNotExecuted();

        $this->listener->postPersist($this->createEvent(new \stdClass()));
    }

    public function testPostUpdate()
    {
        $entity = new WorkflowDefinition();

        $this->assertImportExecuted($entity, ['configuration']);

        $this->listener->postUpdate($this->createEvent($entity));
    }

    public function testPostUpdateWithUnsupportedEntity()
    {
        $this->assertImportNotExecuted();

        $this->listener->postRemove($this->createEvent(new \stdClass()));
    }

    public function testPostRemove()
    {
        $this->listener->postRemove($this->createEvent(new \stdClass()));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Instance of Oro\Bundle\WorkflowBundle\Model\ProcessImport expected.
     */
    public function testProcessImportException()
    {
        $entity = new WorkflowDefinition();

        $this->generator->expects($this->once())
            ->method('generateForScheduledTransition')
            ->with($entity)
            ->willReturn(['configuration']);

        $this->importLink->expects($this->once())->method('getService')->willReturn(new \stdClass());

        $this->listener->postPersist($this->createEvent($entity));
    }

    /**
     * @param object $entity
     * @return LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEvent($entity)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $event */
        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getEntity')->willReturn($entity);

        return $event;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param array $configuration
     */
    protected function assertImportExecuted(WorkflowDefinition $workflowDefinition, array $configuration)
    {
        $this->generator->expects($this->once())
            ->method('generateForScheduledTransition')
            ->with($workflowDefinition)
            ->willReturn($configuration);

        /** @var ProcessImport|\PHPUnit_Framework_MockObject_MockObject $import */
        $import = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessImport')
            ->disableOriginalConstructor()
            ->getMock();
        $import->expects($this->once())->method('import')->with($configuration);

        $this->importLink->expects($this->once())
            ->method('getService')
            ->willReturn($import);
    }

    protected function assertImportNotExecuted()
    {
        $this->generator->expects($this->never())->method($this->anything());
        $this->importLink->expects($this->never())->method($this->anything());
    }
}
