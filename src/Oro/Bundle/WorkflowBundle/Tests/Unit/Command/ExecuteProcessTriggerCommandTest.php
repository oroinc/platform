<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Console\Input\Input;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\WorkflowBundle\Command\ExecuteProcessTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Command\Stub\TestOutput;

class ExecuteProcessTriggerCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExecuteProcessTriggerCommand */
    private $command;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface */
    private $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    private $managerRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $processHandler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Input */
    private $input;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository */
    private $repo;

    /** @var TestOutput */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->with('OroWorkflowBundle:ProcessTrigger')
            ->willReturn($this->repo);

        $this->managerRegistry    = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroWorkflowBundle:ProcessTrigger')
            ->willReturn($em);

        $this->managerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $this->processHandler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new ExecuteProcessTriggerCommand();
        $this->command->setContainer($this->container);

        $this->input   = $this->getMockForAbstractClass('Symfony\Component\Console\Input\InputInterface');
        $this->output = new TestOutput();
    }

    /**
     * {@inheritdoc}
     */
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
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('id')
            ->will($this->returnValue($id));

        $processTrigger = $this->createProcessTrigger($id);

        $this->repo
            ->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($processTrigger);

            $stub = $exception ? $this->throwException($exception) : $this->returnSelf();
            $this->processHandler->expects($this->once())
                ->method('handleTrigger')
                ->withAnyParameters()
                ->will($stub);

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
        $prop  = $class->getProperty('id');
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
                    'Executing process trigger #1 "label" (name)',
                    'Process trigger #1 execution name successfully finished in',
                ],
            ],
            'wrong id' => [
                'id' => 2,
                'output' => [
                    'Executing process trigger #2 "label" (name)',
                    'Process trigger #2 execution failed: Process 1 exception',
                ],
                'exception' => new \Exception('Process 1 exception'),
            ],
        ];
    }

    public function testExecuteEmptyIdError()
    {
        $this->expectContainerGetManagerRegistryAndProcessHandler();

        $id = 1;
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('id')
            ->will($this->returnValue($id));

        $this->processHandler->expects($this->never())
            ->method($this->anything());

        $this->command->execute($this->input, $this->output);

        $this->assertAttributeEquals(
            ['Process trigger not found'],
            'messages',
            $this->output
        );
    }

    public function testExecuteEmptyNoIdSpecified()
    {
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('id')
            ->will($this->returnValue(''));

        $this->processHandler->expects($this->never())
            ->method($this->anything());

        $this->command->execute($this->input, $this->output);

        $this->assertAttributeEquals(
            ['No process trigger identifier defined'],
            'messages',
            $this->output
        );
    }

    protected function expectContainerGetManagerRegistryAndProcessHandler()
    {
        $this->container->expects($this->atLeastOnce())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['doctrine', 1, $this->managerRegistry],
                        ['oro_workflow.process.process_handler', 1, $this->processHandler],
                    ]
                )
            );
    }
}
