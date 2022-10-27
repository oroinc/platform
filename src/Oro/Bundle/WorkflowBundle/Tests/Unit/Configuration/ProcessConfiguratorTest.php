<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionsConfigurator;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggersConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\LoggerInterface;

class ProcessConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = ProcessDefinition::class;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var ProcessDefinitionsConfigurator|\PHPUnit\Framework\MockObject\MockObject */
    private $definitionsConfigurator;

    /** @var ProcessTriggersConfigurator|\PHPUnit\Framework\MockObject\MockObject */
    private $triggersConfigurator;

    /** @var ProcessConfigurator */
    private $processConfigurator;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->definitionsConfigurator = $this->createMock(ProcessDefinitionsConfigurator::class);
        $this->triggersConfigurator = $this->createMock(ProcessTriggersConfigurator::class);

        $this->processConfigurator = new ProcessConfigurator(
            $this->managerRegistry,
            $this->definitionsConfigurator,
            $this->triggersConfigurator,
            self::CLASS_NAME
        );
    }

    public function testConfigureProcesses()
    {
        $processConfigurations = [
            ProcessConfigurationProvider::NODE_DEFINITIONS => ['...definitions config'],
            ProcessConfigurationProvider::NODE_TRIGGERS => ['...triggers config']
        ];

        $this->definitionsConfigurator->expects($this->once())
            ->method('configureDefinitions')
            ->with(['...definitions config']);

        $this->definitionsConfigurator->expects($this->once())
            ->method('flush');

        //definitions repository mock
        $definitionsRepositoryMock = $this->assertObjectManagerCalledForRepository(self::CLASS_NAME);
        $definitionsRepositoryMock->expects($this->once())
            ->method('findAll')
            ->willReturn(['...definitions here']);

        $this->triggersConfigurator->expects($this->once())
            ->method('configureTriggers')
            ->with(['...triggers config']);

        $this->triggersConfigurator->expects($this->once())
            ->method('flush');

        $this->processConfigurator->configureProcesses($processConfigurations);
    }

    public function testSetLogger()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->assertNotSame($logger, ReflectionUtil::getPropertyValue($this->processConfigurator, 'logger'));
        $this->processConfigurator->setLogger($logger);
        $this->assertSame($logger, ReflectionUtil::getPropertyValue($this->processConfigurator, 'logger'));
    }

    public function testRemoveProcesses()
    {
        $definition = new ProcessDefinition();
        $names = [
            'process_exist',
            'process_not_exist',
        ];

        //definitions repository mock
        $definitionsRepositoryMock = $this->assertObjectManagerCalledForRepository(self::CLASS_NAME);
        $definitionsRepositoryMock->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                ['process_exist', $definition],
                ['process_not_exist', null],
            ]);

        $this->definitionsConfigurator->expects($this->once())
            ->method('removeDefinition')
            ->with('process_exist');

        $this->definitionsConfigurator->expects($this->once())
            ->method('flush');

        $this->triggersConfigurator->expects($this->once())
            ->method('removeDefinitionTriggers')
            ->with($definition);

        $this->triggersConfigurator->expects($this->once())
            ->method('flush');

        $this->processConfigurator->removeProcesses($names);
    }

    /**
     * @param string $entityClass
     * @return ObjectRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertObjectManagerCalledForRepository($entityClass)
    {
        $repository = $this->createMock(ObjectRepository::class);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repository);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($objectManager);

        return $repository;
    }
}
