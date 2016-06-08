<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionsConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Component\Testing\Unit\EntityTrait;

class ProcessDefinitionsConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ProcessConfigurationBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationBuilder;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $managerRegistry;

    /** @var string|\PHPUnit_Framework_MockObject_MockObject */
    protected $definitionClass;

    /** @var ProcessDefinitionsConfigurator */
    protected $processDefinitionsConfigurator;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    private $objectManager;

    protected function setUp()
    {
        $this->configurationBuilder = $this->getMock(
            'Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder'
        );

        $this->repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $this->objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->definitionClass = 'Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition';
        $this->processDefinitionsConfigurator = new ProcessDefinitionsConfigurator(
            $this->configurationBuilder,
            $this->managerRegistry,
            $this->definitionClass
        );
    }

    public function testConfigureDefinitions()
    {
        $definitionsConfiguration = ['...configuration'];

        $this->assertManagerRegistryCalled($this->definitionClass);
        $this->assertObjectManagerCalledForRepository($this->definitionClass);

        /**@var ProcessDefinition|\PHPUnit_Framework_MockObject_MockObject $definitionStoredExistent */
        $definitionStoredExistent = $this->getMock($this->definitionClass);

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

        $definitionStoredExistent->expects($this->once())->method('import')->with($newDefinitionExistent);
        $this->processDefinitionsConfigurator->configureDefinitions($definitionsConfiguration);

        $this->assertAttributeEquals(true, 'dirty', $this->processDefinitionsConfigurator);
        $this->assertAttributeEquals([$newDefinitionNonExistent], 'toPersist', $this->processDefinitionsConfigurator);
    }

    public function testFlush()
    {
        $processDefinitionToPersist = new ProcessDefinition();
        $processDefinitionToRemove = new ProcessDefinition();
        $this->setValue($this->processDefinitionsConfigurator, 'dirty', true);
        $this->setValue($this->processDefinitionsConfigurator,'toPersist',[$processDefinitionToPersist]);
        $this->setValue($this->processDefinitionsConfigurator, 'toRemove', [$processDefinitionToRemove]);

        $this->assertManagerRegistryCalled($this->definitionClass);
        $this->objectManager->expects($this->once())->method('persist')->with($processDefinitionToPersist);
        $this->objectManager->expects($this->once())->method('remove')->with($processDefinitionToRemove);
        $this->objectManager->expects($this->once())->method('flush');

        $this->processDefinitionsConfigurator->flush();

        $this->assertAttributeEquals(false, 'dirty', $this->processDefinitionsConfigurator);
    }

    public function testEmptyFlush()
    {
        $this->assertManagerRegistryCalled($this->definitionClass);
        $this->objectManager->expects($this->never())->method($this->anything());
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

        $this->processDefinitionsConfigurator->removeDefinition($definitionName);

        $this->assertAttributeEquals(true, 'dirty', $this->processDefinitionsConfigurator);

        $this->assertAttributeEquals([$definitionObject], 'toRemove', $this->processDefinitionsConfigurator);
    }
}
