<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\EntitySerializer\EntityConfig;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowActivationException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;

class WorkflowSystemConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /**@var WorkflowSystemConfigManager */
    protected $manager;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();
        $this->eventDispatcher = $this->getMock(EventDispatcher::class);

        $this->manager = new WorkflowSystemConfigManager(
            $this->configManager,
            $this->eventDispatcher
        );
    }

    public function testIsActiveWorkflowTrue()
    {
        $definition = (new WorkflowDefinition())
            ->setName('workflow_name')
            ->setRelatedEntity('stdClass');

        //->getEntityConfig
        $entityConfig = $this->emulateGetEntityConfig('stdClass');

        $entityConfig->expects($this->once())->method('get')->willReturn(['workflow_name', 'workflow_name2']);

        $this->assertTrue(
            $this->manager->isActiveWorkflow($definition),
            'isActiveWorkflow should return true because non_active_workflow_name is in list of active'
        );
    }

    public function testIsActiveWorkflowFalse()
    {
        $definition = (new WorkflowDefinition())
            ->setName('non_active_workflow_name')
            ->setRelatedEntity('stdClass');
        //->getEntityConfig
        $entityConfig = $this->emulateGetEntityConfig('stdClass');

        $entityConfig->expects($this->once())->method('get')->willReturn(['workflow_name', 'workflow_name2']);

        $this->assertFalse(
            $this->manager->isActiveWorkflow($definition),
            'isActiveWorkflow should return false because non_active_workflow_name is not in list of active'
        );
    }

    public function testSetWorkflowActive()
    {
        $definition = (new WorkflowDefinition())
            ->setName('workflow_name')
            ->setRelatedEntity('stdClass');
        //->getEntityConfig
        $entityConfig = $this->emulateGetEntityConfig('stdClass');
        $entityConfig->expects($this->once())->method('get')->willReturn(['workflow_name2']);
        $entityConfig->expects($this->once())
            ->method('set')
            ->with('active_workflows', ['workflow_name2', 'workflow_name']);

        //->persistEntityConfig
        $this->configManager->expects($this->once())->method('persist')->with($entityConfig);
        $this->configManager->expects($this->once())->method('flush');

        //trigger activation event
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo('oro.workflow.activated'),
                $this->logicalAnd(
                    $this->isInstanceOf(WorkflowChangesEvent::class),
                    $this->attributeEqualTo('definition', $definition)
                )
            );

        $this->manager->setWorkflowActive($definition);
    }

    public function testExceptionSetWorkflowActiveAlready()
    {
        $definition = (new WorkflowDefinition())
            ->setName('workflow_name')
            ->setRelatedEntity('stdClass');
        //->getEntityConfig
        $entityConfig = $this->emulateGetEntityConfig('stdClass');
        $entityConfig->expects($this->once())->method('get')->willReturn(['workflow_name2', 'workflow_name']);

        $this->setExpectedException(
            WorkflowActivationException::class,
            'Can not activate workflow `workflow_name` again. Already activated.'
        );

        $this->manager->setWorkflowActive($definition);
    }

    public function testSetWorkflowInactive()
    {
        $definition = (new WorkflowDefinition())
            ->setName('workflow_name')
            ->setRelatedEntity('stdClass');
        //->getEntityConfig
        $entityConfig = $this->emulateGetEntityConfig('stdClass');
        $entityConfig->expects($this->once())->method('get')->willReturn(['workflow_name', 'workflow_name2']);
        $entityConfig->expects($this->once())->method('set')->with('active_workflows', ['workflow_name2']);

        //->persistEntityConfig
        $this->configManager->expects($this->once())->method('persist')->with($entityConfig);
        $this->configManager->expects($this->once())->method('flush');

        //trigger activation event
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo('oro.workflow.deactivated'),
                $this->logicalAnd(
                    $this->isInstanceOf(WorkflowChangesEvent::class),
                    $this->attributeEqualTo('definition', $definition)
                )
            );

        $this->manager->setWorkflowInactive($definition);
    }

    public function testExceptionSetWorkflowInactiveAlready()
    {
        $definition = (new WorkflowDefinition())
            ->setName('workflow_name')
            ->setRelatedEntity('stdClass');
        //->getEntityConfig
        $entityConfig = $this->emulateGetEntityConfig('stdClass');
        $entityConfig->expects($this->once())->method('get')->willReturn(['workflow_name2']);

        $this->setExpectedException(
            WorkflowActivationException::class,
            'Can not deactivate workflow `workflow_name`. It is currently not active.'
        );

        $this->manager->setWorkflowInactive($definition);
    }

    public function testIsConfigurable()
    {
        $wfConfigProvider = $this->getMockBuilder(ConfigProvider::class)->disableOriginalConstructor()->getMock();
        $wfConfigProvider->expects($this->once())->method('hasConfig')->with(EntityStub::class)->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('workflow')
            ->willReturn($wfConfigProvider);
        $this->assertTrue($this->manager->isConfigurable(EntityStub::class));
    }

    /**
     * @param $entityClass
     * @return EntityConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function emulateGetEntityConfig($entityClass)
    {
        $entityConfig = $this->getMock(ConfigInterface::class);
        $wfConfigProvider = $this->getMockBuilder(ConfigProvider::class)->disableOriginalConstructor()->getMock();
        $wfConfigProvider->expects($this->once())->method('hasConfig')->with($entityClass)->willReturn(true);
        $wfConfigProvider->expects($this->once())->method('getConfig')->with($entityClass)->willReturn($entityConfig);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('workflow')
            ->willReturn($wfConfigProvider);

        return $entityConfig;
    }

    public function testGetActiveWorkflowNamesByEntity()
    {
        $entity = new EntityStub(24);

        $entityConfig = $this->emulateGetEntityConfig(EntityStub::class);

        $entityConfig->expects($this->once())
            ->method('get')
            ->with('active_workflows', false, [])
            ->willReturn(
                [
                    'some_workflow_1',
                    'some_workflow_2',
                ]
            );

        $result = $this->manager->getActiveWorkflowNamesByEntity($entity);

        $this->assertEquals(
            [
                'some_workflow_1',
                'some_workflow_2',
            ],
            $result,
            'Expected same as config returned'
        );
    }

    public function testEntityIsNotConfigurable()
    {
        $definition = (new WorkflowDefinition())
            ->setName('name')
            ->setRelatedEntity('stdClass');

        $wfConfigProvider = $this->getMockBuilder(ConfigProvider::class)->disableOriginalConstructor()->getMock();
        $wfConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($definition->getRelatedEntity())
            ->willReturn(false);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('workflow')
            ->willReturn($wfConfigProvider);

        $this->setExpectedException(WorkflowException::class, 'Entity stdClass is not configurable');

        $this->manager->isActiveWorkflow($definition);
    }
}
