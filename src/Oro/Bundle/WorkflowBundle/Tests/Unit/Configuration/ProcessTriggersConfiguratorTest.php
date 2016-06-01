<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggersConfigurator;
use Oro\Bundle\WorkflowBundle\Model\ProcessTriggerCronScheduler;

class ProcessTriggersConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProcessConfigurationBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationBuilder;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $managerRegistry;

    /** @var string|\PHPUnit_Framework_MockObject_MockObject */
    protected $triggerEntityClass;

    /**
     * @var ProcessTriggerCronScheduler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processCronScheduler;

    /**
     * @var ProcessTriggersConfigurator
     */
    protected $processTriggersImport;

    /**
     * @var ProcessTriggerRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->configurationBuilder = $this->getMock(
            'Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder'
        );

        $this->repository = $this->getMockBuilder(
            'Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository'
        )->disableOriginalConstructor()->getMock();

        $this->objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->processCronScheduler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessTriggerScheduler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->triggerEntityClass = 'Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger';
        $this->processTriggersImport = new ProcessTriggersConfigurator(
            $this->configurationBuilder,
            $this->managerRegistry,
            $this->triggerEntityClass,
            $this->processCronScheduler
        );
    }

    public function testImport()
    {
        $triggersConfiguration = ['...triggers_configuration'];
        $definition = new ProcessDefinition();
        $definition->setName('definition_name');
        $definitions = [$definition];

        $existentNewTrigger = new ProcessTrigger();
        $nonExistentNewTrigger = new ProcessTrigger();

        $this->configurationBuilder->expects($this->once())
            ->method('buildProcessTriggers')
            ->with($triggersConfiguration, ['definition_name' => $definition])
            ->willReturn([$existentNewTrigger, $nonExistentNewTrigger]);

        $this->assertManagerRegistryCalled($this->triggerEntityClass);
        $this->assertObjectManagerCalledForRepository($this->triggerEntityClass);

        /** @var ProcessTrigger|\PHPUnit_Framework_MockObject_MockObject */
        $mockExistentTrigger = $this->getMock($this->triggerEntityClass);

        $this->repository->expects($this->exactly(2))->method('findEqualTrigger')->willReturnMap(
            [
                [$existentNewTrigger, $mockExistentTrigger],
                [$nonExistentNewTrigger, null]
            ]
        );

        $mockExistentTrigger->expects($this->once())->method('import')->with($existentNewTrigger);

        $this->objectManager->expects($this->once())->method('persist')->with($nonExistentNewTrigger);

        $this->objectManager->expects($this->once())->method('flush');

        //schedules

        $this->repository->expects($this->once())->method('findAllCronTriggers')->willReturn([$mockExistentTrigger]);

        $this->processCronScheduler->expects($this->once())->method('add')->with($mockExistentTrigger);

        $schedulesCreated = [new Schedule()];
        $this->processCronScheduler->expects($this->once())->method('flush')->willReturn($schedulesCreated);

        //run import
        $this->processTriggersImport->configureTriggers($triggersConfiguration, $definitions);
        $this->assertEquals($schedulesCreated, $this->processTriggersImport->getCreatedSchedules());
    }

    /**
     * @param string $entityClass
     */
    public function assertManagerRegistryCalled($entityClass)
    {
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($this->objectManager);
    }

    /**
     * @param string $entityClass
     */
    public function assertObjectManagerCalledForRepository($entityClass)
    {
        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($this->repository);
    }

    public function testGetCreatedSchedules()
    {
        $this->assertEquals([], $this->processTriggersImport->getCreatedSchedules(), 'no imports called. result []');
    }
}
