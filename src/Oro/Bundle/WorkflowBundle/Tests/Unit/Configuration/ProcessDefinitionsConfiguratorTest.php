<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionsConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class ProcessDefinitionsConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProcessConfigurationBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationBuilder;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var string|\PHPUnit\Framework\MockObject\MockObject */
    private $definitionClass;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $objectManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ProcessDefinitionsConfigurator */
    private $processDefinitionsConfigurator;

    protected function setUp(): void
    {
        $this->configurationBuilder = $this->createMock(ProcessConfigurationBuilder::class);
        $this->repository = $this->createMock(ObjectRepository::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->definitionClass = ProcessDefinition::class;

        $this->processDefinitionsConfigurator = new ProcessDefinitionsConfigurator(
            $this->configurationBuilder,
            $this->managerRegistry,
            $this->definitionClass
        );
        $this->processDefinitionsConfigurator->setLogger($this->logger);
    }

    public function testConfigureDefinitions()
    {
        $definitionsConfiguration = ['...configuration'];

        $this->assertManagerRegistryCalled($this->definitionClass);
        $this->assertObjectManagerCalledForRepository($this->definitionClass);

        $definitionStoredExistent = new ProcessDefinition();

        $newDefinitionExistent = new ProcessDefinition();
        $newDefinitionExistent->setName('existent');
        $newDefinitionNonExistent = new ProcessDefinition();
        $newDefinitionNonExistent->setName('nonExistent');

        $this->configurationBuilder->expects($this->once())
            ->method('buildProcessDefinitions')
            ->with($definitionsConfiguration)
            ->willReturn([$newDefinitionExistent, $newDefinitionNonExistent]);

        $this->repository->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                ['existent', $definitionStoredExistent],
                ['nonExistent', null]
            ]);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    '> process definition: "{definition_name}" - {action}',
                    ['definition_name' => $newDefinitionExistent->getName(), 'action' => 'updated']
                ],
                [
                    '> process definition: "{definition_name}" - {action}',
                    ['definition_name' => $newDefinitionNonExistent->getName(), 'action' => 'created']
                ]
            );

        $this->processDefinitionsConfigurator->configureDefinitions($definitionsConfiguration);
        $this->assertEquals($newDefinitionExistent, $definitionStoredExistent);

        self::assertTrue($this->getDirtyPropertyValue());
        self::assertEquals([$newDefinitionNonExistent], $this->getToPersistPropertyValue());
    }

    public function testFlush()
    {
        $processDefinitionToPersist = new ProcessDefinition();
        $processDefinitionToRemove = new ProcessDefinition();
        $processDefinitionToRemoveNotManaged = new ProcessDefinition();

        $this->setValue($this->processDefinitionsConfigurator, 'dirty', true);
        $this->setValue($this->processDefinitionsConfigurator, 'toPersist', [$processDefinitionToPersist]);
        $this->setValue(
            $this->processDefinitionsConfigurator,
            'toRemove',
            [$processDefinitionToRemove, $processDefinitionToRemoveNotManaged]
        );

        $this->assertManagerRegistryCalled($this->definitionClass);
        $this->objectManager->expects($this->once())
            ->method('persist')
            ->with($processDefinitionToPersist);
        $this->objectManager->expects($this->exactly(2))
            ->method('contains')
            ->willReturnMap([
                [$processDefinitionToRemove, true],
                [$processDefinitionToRemoveNotManaged, false]
            ]);
        $this->objectManager->expects($this->once())
            ->method('remove')
            ->with($processDefinitionToRemove);
        $this->objectManager->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Process definitions configuration updates are stored into database');

        $this->processDefinitionsConfigurator->flush();

        self::assertFalse($this->getDirtyPropertyValue());
    }

    public function testEmptyFlush()
    {
        $this->assertManagerRegistryCalled($this->definitionClass);
        $this->objectManager->expects($this->never())
            ->method($this->anything());

        $this->logger->expects($this->once())
            ->method('info')
            ->with('No process definitions configuration updates detected. Nothing flushed into DB');

        $this->processDefinitionsConfigurator->flush();
    }

    private function assertManagerRegistryCalled(string $entityClass): void
    {
        $this->managerRegistry->expects($this->any())
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

    public function testRemoveDefinitions()
    {
        $definitionName = 'definitionName';
        $definitionObject = (new ProcessDefinition())->setName($definitionName);

        $this->assertManagerRegistryCalled($this->definitionClass);
        $this->assertObjectManagerCalledForRepository($this->definitionClass);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($definitionName)
            ->willReturn($definitionObject);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                '> process definition: "{definition_name}" - {action}',
                ['definition_name' => $definitionName, 'action' => 'deleted']
            );

        $this->processDefinitionsConfigurator->removeDefinition($definitionName);

        self::assertTrue($this->getDirtyPropertyValue());
        self::assertEquals([$definitionObject], $this->getToRemovePropertyValue());
    }

    /**
     * @return mixed
     */
    private function getDirtyPropertyValue()
    {
        return ReflectionUtil::getPropertyValue($this->processDefinitionsConfigurator, 'dirty');
    }

    /**
     * @return mixed
     */
    private function getToRemovePropertyValue()
    {
        return ReflectionUtil::getPropertyValue($this->processDefinitionsConfigurator, 'toRemove');
    }

    /**
     * @return mixed
     */
    private function getToPersistPropertyValue()
    {
        return ReflectionUtil::getPropertyValue($this->processDefinitionsConfigurator, 'toPersist');
    }
}
