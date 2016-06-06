<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionsConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

class ProcessDefinitionsConfiguratorTest extends \PHPUnit_Framework_TestCase
{
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

    public function testImport()
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
        $this->objectManager->expects($this->once())->method('persist')->with($newDefinitionNonExistent);
        $this->processDefinitionsConfigurator->configureDefinitions($definitionsConfiguration);
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
}
