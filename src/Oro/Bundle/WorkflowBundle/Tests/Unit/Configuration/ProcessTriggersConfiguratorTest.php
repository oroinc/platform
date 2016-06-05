<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggersConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Bundle\WorkflowBundle\Model\ProcessTriggerCronScheduler;

class ProcessTriggersConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProcessConfigurationBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationBuilder;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $managerRegistry;

    /** @var string|\PHPUnit_Framework_MockObject_MockObject */
    protected $triggerEntityClass;

    /** @var ProcessTriggerCronScheduler|\PHPUnit_Framework_MockObject_MockObject */
    protected $processCronScheduler;

    /** @var ProcessTriggersConfigurator */
    protected $processTriggersImport;

    /** @var ProcessTriggerRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
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
        $this->processCronScheduler = $this
            ->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessTriggerCronScheduler')
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
        $triggersConfiguration = ['definition_name' => [['exist'], ['not_exist']]];
        $definition = new ProcessDefinition();
        $definition->setName('definition_name');
        $definitions = ['definition_name' => $definition];

        $existentNewTrigger = new ProcessTrigger();
        $existentNewTrigger->setDefinition($definition);
        $nonExistentNewTrigger = new ProcessTrigger();

        $this->configurationBuilder->expects($this->exactly(2))
            ->method('buildProcessTrigger')
            ->willReturnMap([
                [['exist'], $definition, $existentNewTrigger],
                [['not_exist'], $definition, $nonExistentNewTrigger],
            ]);

        $this->assertManagerRegistryCalled($this->triggerEntityClass);
        $this->assertObjectManagerCalledForRepository($this->triggerEntityClass);

        /** @var ProcessTrigger|\PHPUnit_Framework_MockObject_MockObject $mockExistentTrigger */
        $mockExistentTrigger = $this->getMock($this->triggerEntityClass);

        $nonExistentNewTrigger->setDefinition($definition);
        $mockExistentTrigger->expects($this->once())->method('import')->with($existentNewTrigger);
        $mockExistentTrigger->expects($this->once())->method('isDefinitiveEqual')->willReturn($mockExistentTrigger);
        $this->objectManager->expects($this->once())->method('persist')->with($existentNewTrigger);
        $this->repository->expects($this->once())->method('findByDefinition')->willReturn([$mockExistentTrigger]);

        //run import
        $this->processTriggersImport->configureTriggers($triggersConfiguration, $definitions);
    }


    /**
     * @dataProvider flushDataProvider
     * @param bool $dirty
     * @param array $triggers
     * @param $expectedSchedulesCount
     */
    public function testFlush($dirty, array $triggers, $expectedSchedulesCount)
    {
        $reflection = new \ReflectionClass('Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggersConfigurator');
        $dirtyProperty = $reflection->getProperty('dirty');
        $dirtyProperty->setAccessible(true);
        $dirtyProperty->setValue($this->processTriggersImport, $dirty);
        $triggersProperty = $reflection->getProperty('triggers');
        $triggersProperty->setAccessible(true);
        $triggersProperty->setValue($this->processTriggersImport, $triggers);

        $this->managerRegistry->expects($this->exactly((int) $dirty))
            ->method('getManagerForClass')
            ->with($this->triggerEntityClass)
            ->willReturn($this->objectManager);

        $this->processCronScheduler
            ->expects($this->exactly($expectedSchedulesCount))
            ->method('add');

        $this->processTriggersImport->flush();

        $this->assertFalse($dirtyProperty->getValue($this->processTriggersImport));
    }

    /**
     * @return array
     */
    public function flushDataProvider()
    {
        $triggerWithCron = new ProcessTrigger();
        $triggerWithCron->setCron('* * * * *');
        $triggerWithoutCron = new ProcessTrigger();

        return [
            'no changes' => [
                'dirty' => false,
                'triggers' => [],
                'expected' => 0,
            ],
            'no triggers' => [
                'dirty' => true,
                'triggers' => [],
                'expected' => 0,
            ],
            'with triggers' => [
                'dirty' => true,
                'triggers' => [$triggerWithCron, $triggerWithoutCron],
                'expected' => 1,
            ],
        ];
    }

    /**
     * @param string $entityClass
     */
    public function assertManagerRegistryCalled($entityClass)
    {
        $this->managerRegistry->expects($this->atLeastOnce())
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
}
