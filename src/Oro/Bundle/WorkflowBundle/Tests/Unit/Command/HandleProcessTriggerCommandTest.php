<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HandleProcessTriggerCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var HandleProcessTriggerCommand */
    private $command;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $managerRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $processHandler;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Input */
    private $input;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository */
    private $repo;

    /** @var OutputStub */
    private $output;

    protected function setUp()
    {
        $this->repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->with('OroWorkflowBundle:ProcessTrigger')
            ->willReturn($this->repo);

        $this->managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroWorkflowBundle:ProcessTrigger')
            ->willReturn($em);
        $this->managerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $this->processHandler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new HandleProcessTriggerCommand();
        $this->command->setContainer($this->container);

        $this->input = $this->getMockForAbstractClass('Symfony\Component\Console\Input\InputInterface');
        $this->output = new OutputStub();
    }

    protected function tearDown()
    {
        unset($this->repo, $this->processHandler, $this->managerRegistry, $this->input, $this->output, $this->command);
    }

    public function testConfigure()
    {
        $this->command->configure();

        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }

    /**
     * @param int $id
     * @param array $expectedOutput
     * @param \Exception $exception
     * @dataProvider executeProvider
     */
    public function testExecute($id, $expectedOutput, \Exception $exception = null)
    {
        $this->expectContainerGetManagerRegistryAndProcessHandler();
        $this->input->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['id', $id],
                ['name', 'name'],
            ]);

        $processTrigger = $this->createProcessTrigger($id);
        $processData = new ProcessData();

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($processTrigger);

        $this->processHandler->expects($this->once())
            ->method('handleTrigger')
            ->with($processTrigger, $processData)
            ->will($exception ? $this->throwException($exception) : $this->returnSelf());

        $this->processHandler->expects($this->once())
            ->method('finishTrigger')
            ->with($processTrigger, $processData);

        if ($exception) {
            $this->expectException(get_class($exception));
            $this->expectExceptionMessage($exception->getMessage());
        }

        $this->command->execute($this->input, $this->output);

        $messages = $this->getObjectAttribute($this->output, 'messages');

        $found = 0;
        foreach ($messages as $message) {
            foreach ($expectedOutput as $expected) {
                if (strpos($message, $expected) !== false) {
                    $found++;
                }
            }
        }

        $this->assertCount($found, $expectedOutput);
    }

    /**
     * @param int $id
     * @return ProcessTrigger
     */
    protected function createProcessTrigger($id)
    {
        $definition = new ProcessDefinition();
        $definition
            ->setName('name')
            ->setLabel('label')
            ->setRelatedEntity('\StdClass');

        $processTrigger = new ProcessTrigger();
        $processTrigger->setDefinition($definition);

        $class = new \ReflectionClass($processTrigger);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($processTrigger, $id);

        return $processTrigger;
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        return [
            'valid id' => [
                'id' => 1,
                'output' => [
                    'Trigger #1 of process "name" successfully finished in',
                ],
            ],
            'wrong id' => [
                'id' => 2,
                'output' => [
                    'Trigger #2 of process "name" failed: Process 1 exception',
                ],
                'exception' => new \RuntimeException('Process 1 exception'),
            ],
        ];
    }

    public function testExecuteEmptyIdError()
    {
        $this->expectContainerGetManagerRegistryAndProcessHandler();

        $this->input->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['id', 1],
                ['name', 'name'],
            ]);

        $this->processHandler->expects($this->never())->method($this->anything());

        $this->command->execute($this->input, $this->output);

        $this->assertAttributeEquals(['Process trigger not found'], 'messages', $this->output);
    }

    public function testExecuteEmptyNoIdSpecified()
    {
        $this->input->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['id', '123a'],
                ['name', 'name'],
            ]);

        $this->processHandler->expects($this->never())->method($this->anything());

        $this->command->execute($this->input, $this->output);

        $this->assertAttributeEquals(['No process trigger identifier defined'], 'messages', $this->output);
    }

    public function testExecuteWrongNameSpecified()
    {
        $this->expectContainerGetManagerRegistryAndProcessHandler();

        $id = 1;
        $this->input->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['id', $id],
                ['name', 'wrong_name'],
            ]);

        $processTrigger = $this->createProcessTrigger($id);

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($processTrigger);

        $this->processHandler->expects($this->never())->method($this->anything());

        $this->command->execute($this->input, $this->output);

        $this->assertAttributeEquals(
            ['Trigger not found in process definition "wrong_name"'],
            'messages',
            $this->output
        );
    }

    protected function expectContainerGetManagerRegistryAndProcessHandler()
    {
        $this->container->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['doctrine', 1, $this->managerRegistry],
                ['oro_workflow.process.process_handler', 1, $this->processHandler],
            ]);
    }
}
