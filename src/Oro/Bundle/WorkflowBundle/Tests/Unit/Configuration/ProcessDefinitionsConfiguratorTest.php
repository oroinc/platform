<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionsConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ProcessDefinitionsConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProcessConfigurationBuilder|MockObject */
    private $configurationBuilder;

    /** @var ManagerRegistry|MockObject */
    private $managerRegistry;

    /** @var string|MockObject */
    private $definitionClass;

    /** @var ProcessDefinitionsConfigurator */
    private $processDefinitionsConfigurator;

    /** @var ObjectRepository|MockObject */
    private $repository;

    /** @var ObjectManager|MockObject */
    private $objectManager;

    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->configurationBuilder = $this->createMock(ProcessConfigurationBuilder::class);

        $this->repository = $this->createMock(ObjectRepository::class);
        $this->objectManager = $this->createMock(ObjectManager::class);

        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->definitionClass = ProcessDefinition::class;

        $this->logger = $this->createMock(LoggerInterface::class);

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

        $this->repository->expects($this->exactly(2))->method('find')->willReturnMap([
            ['existent', $definitionStoredExistent],
            ['nonExistent', null]
        ]);

        $this->logger->expects($this->at(0))
            ->method('info')
            ->with(
                '> process definition: "{definition_name}" - {action}',
                ['definition_name' => $newDefinitionExistent->getName(), 'action' => 'updated']
            );
        $this->logger->expects($this->at(1))
            ->method('info')
            ->with(
                '> process definition: "{definition_name}" - {action}',
                ['definition_name' => $newDefinitionNonExistent->getName(), 'action' => 'created']
            );

        $this->processDefinitionsConfigurator->configureDefinitions($definitionsConfiguration);
        $this->assertEquals($newDefinitionExistent, $definitionStoredExistent);

        static::assertTrue($this->getDirtyPropertyValue());
        static::assertEquals([$newDefinitionNonExistent], $this->getToPersistPropertyValue());
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
        $this->objectManager->expects($this->once())->method('persist')->with($processDefinitionToPersist);
        $this->objectManager->expects($this->exactly(2))
            ->method('contains')
            ->willReturnMap(
                [
                    [$processDefinitionToRemove, true],
                    [$processDefinitionToRemoveNotManaged, false]
                ]
            );
        $this->objectManager->expects($this->once())->method('remove')->with($processDefinitionToRemove);
        $this->objectManager->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Process definitions configuration updates are stored into database');

        $this->processDefinitionsConfigurator->flush();

        static::assertFalse($this->getDirtyPropertyValue());
    }

    public function testEmptyFlush()
    {
        $this->assertManagerRegistryCalled($this->definitionClass);
        $this->objectManager->expects($this->never())->method($this->anything());

        $this->logger->expects($this->once())
            ->method('info')
            ->with('No process definitions configuration updates detected. Nothing flushed into DB');

        $this->processDefinitionsConfigurator->flush();
    }

    /**
     * @param string $entityClass
     */
    private function assertManagerRegistryCalled($entityClass)
    {
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($this->objectManager);
    }

    /**
     * @param string $entityClass
     */
    private function assertObjectManagerCalledForRepository($entityClass)
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

        $this->logger->expects($this->at(0))
            ->method('info')
            ->with(
                '> process definition: "{definition_name}" - {action}',
                ['definition_name' => $definitionName, 'action' => 'deleted']
            );

        $this->processDefinitionsConfigurator->removeDefinition($definitionName);

        static::assertTrue($this->getDirtyPropertyValue());
        static::assertEquals([$definitionObject], $this->getToRemovePropertyValue());
    }

    /**
     * @return mixed
     */
    private function getDirtyPropertyValue()
    {
        $property = new \ReflectionProperty(ProcessDefinitionsConfigurator::class, 'dirty');
        $property->setAccessible(true);

        return $property->getValue($this->processDefinitionsConfigurator);
    }

    /**
     * @return mixed
     */
    private function getToRemovePropertyValue()
    {
        $property = new \ReflectionProperty(ProcessDefinitionsConfigurator::class, 'toRemove');
        $property->setAccessible(true);

        return $property->getValue($this->processDefinitionsConfigurator);
    }

    /**
     * @return mixed
     */
    private function getToPersistPropertyValue()
    {
        $property = new \ReflectionProperty(ProcessDefinitionsConfigurator::class, 'toPersist');
        $property->setAccessible(true);

        return $property->getValue($this->processDefinitionsConfigurator);
    }
}
