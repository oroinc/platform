<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggersConfigurator;
use Oro\Bundle\WorkflowBundle\Cron\ProcessTriggerCronScheduler;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class ProcessTriggersConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProcessConfigurationBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationBuilder;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var string|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerEntityClass;

    /** @var ProcessTriggerCronScheduler|\PHPUnit\Framework\MockObject\MockObject */
    private $processCronScheduler;

    /** @var ProcessTriggerRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $objectManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ProcessTriggersConfigurator */
    private $processTriggersConfigurator;

    protected function setUp(): void
    {
        $this->configurationBuilder = $this->createMock(ProcessConfigurationBuilder::class);
        $this->repository = $this->createMock(ProcessTriggerRepository::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->processCronScheduler = $this->createMock(ProcessTriggerCronScheduler::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->triggerEntityClass = ProcessTrigger::class;

        $this->processTriggersConfigurator = new ProcessTriggersConfigurator(
            $this->configurationBuilder,
            $this->processCronScheduler,
            $this->managerRegistry,
            $this->triggerEntityClass
        );
        $this->processTriggersConfigurator->setLogger($this->logger);
    }

    public function testConfigureTriggers()
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

        $existentTrigger = $this->createMock($this->triggerEntityClass);

        $nonExistentNewTrigger->setDefinition($definition)->setCron('42 * * * *');
        $existentTrigger->expects($this->once())
            ->method('import')
            ->with($existentNewTrigger);
        $existentTrigger->expects($this->once())
            ->method('isDefinitiveEqual')
            ->willReturn(true);
        $existentTrigger->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);
        $existentTrigger->expects($this->any())
            ->method('getCron')
            ->willReturn('43 * * * *');

        $unaffectedTrigger = $this->createMock($this->triggerEntityClass);
        $unaffectedTrigger->expects($this->any())
            ->method('isDefinitiveEqual')
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('findByDefinitionName')
            ->willReturn([$existentTrigger, $unaffectedTrigger]);

        //delete unaffected
        $unaffectedTrigger->expects($this->any())
            ->method('getCron')
            ->willReturn('string'); //in dropSchedule
        $this->processCronScheduler->expects($this->once())
            ->method('removeSchedule')
            ->with($unaffectedTrigger);

        $this->logger->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                [
                    '>> process trigger: {definition_name} [{type}] - {action}',
                    ['definition_name' => 'definition_name', 'action' => 'updated', 'type' => 'cron:43 * * * *']
                ],
                [
                    '>> process trigger: {definition_name} [{type}] - {action}',
                    ['definition_name' => 'definition_name', 'action' => 'created', 'type' => 'cron:42 * * * *']
                ],
                [
                    '>> process trigger: {definition_name} [{type}] - {action}',
                    ['definition_name' => '', 'type' => 'cron:string', 'action' => 'deleted']
                ]
            );

        $this->processTriggersConfigurator->configureTriggers($triggersConfiguration, $definitions);

        self::assertTrue($this->getDirtyPropertyValue());
        self::assertEquals([$unaffectedTrigger], $this->getForRemovePropertyValue());
        self::assertEquals([$nonExistentNewTrigger], $this->getForPersistPropertyValue());
    }

    public function testRemoveDefinitionTriggers()
    {
        $this->assertManagerRegistryCalled($this->triggerEntityClass);
        $this->assertObjectManagerCalledForRepository($this->triggerEntityClass);

        $definition = (new ProcessDefinition())->setName('definition_name');

        $trigger = (new ProcessTrigger())->setCron('42 * * * *')->setDefinition($definition);

        $this->repository->expects($this->once())
            ->method('findByDefinitionName')
            ->with('definition_name')
            ->willReturn([$trigger]);

        $this->processCronScheduler->expects($this->once())
            ->method('removeSchedule')
            ->with($trigger);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                '>> process trigger: {definition_name} [{type}] - {action}',
                ['definition_name' => $definition->getName(), 'action' => 'deleted', 'type' => 'cron:42 * * * *']
            );

        $this->processTriggersConfigurator->removeDefinitionTriggers($definition);

        self::assertTrue($this->getDirtyPropertyValue());
        self::assertEquals([$trigger], $this->getForRemovePropertyValue());
    }

    /**
     * @dataProvider flushDataProvider
     */
    public function testFlush(bool $dirty, array $triggers, int $expectedSchedulesCount)
    {
        $this->setValue($this->processTriggersConfigurator, 'dirty', $dirty);
        $this->setValue($this->processTriggersConfigurator, 'triggers', $triggers);

        $this->processCronScheduler->expects($this->exactly($expectedSchedulesCount))
            ->method('add');

        if ($dirty) {
            $this->assertManagerRegistryCalled($this->triggerEntityClass);
            $this->objectManager->expects($this->once())
                ->method('flush');
        } else {
            $this->objectManager->expects($this->never())
                ->method('flush');
        }

        $this->processTriggersConfigurator->flush();

        self::assertFalse($this->getDirtyPropertyValue());
    }

    public function flushDataProvider(): array
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

    public function testFlushRemoves()
    {
        $this->setValue($this->processTriggersConfigurator, 'dirty', true);

        $trigger = new ProcessTrigger();
        $this->setValue($this->processTriggersConfigurator, 'forRemove', [$trigger]);

        $this->assertManagerRegistryCalled($this->triggerEntityClass);

        $this->objectManager->expects($this->once())
            ->method('contains')
            ->with($trigger)
            ->willReturn(true);
        $this->objectManager->expects($this->once())
            ->method('remove')
            ->with($trigger);
        $this->objectManager->expects($this->once())
            ->method('flush');
        $this->processCronScheduler->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('>> process triggers modifications stored in DB');

        $this->processTriggersConfigurator->flush();
    }

    public function testFlushPersists()
    {
        $this->setValue($this->processTriggersConfigurator, 'dirty', true);

        $trigger = new ProcessTrigger();
        $this->setValue($this->processTriggersConfigurator, 'forPersist', [$trigger]);

        $this->assertManagerRegistryCalled($this->triggerEntityClass);

        $this->objectManager->expects($this->once())
            ->method('persist')
            ->with($trigger);
        $this->objectManager->expects($this->once())
            ->method('flush');
        $this->processCronScheduler->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('>> process triggers modifications stored in DB');

        $this->processTriggersConfigurator->flush();
    }

    private function assertManagerRegistryCalled(string $entityClass): void
    {
        $this->managerRegistry->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($this->objectManager);
    }

    private function assertObjectManagerCalledForRepository(string $entityClass): void
    {
        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($this->repository);
    }

    private function getDirtyPropertyValue(): mixed
    {
        return ReflectionUtil::getPropertyValue($this->processTriggersConfigurator, 'dirty');
    }

    private function getForRemovePropertyValue(): mixed
    {
        return ReflectionUtil::getPropertyValue($this->processTriggersConfigurator, 'forRemove');
    }

    private function getForPersistPropertyValue(): mixed
    {
        return ReflectionUtil::getPropertyValue($this->processTriggersConfigurator, 'forPersist');
    }
}
