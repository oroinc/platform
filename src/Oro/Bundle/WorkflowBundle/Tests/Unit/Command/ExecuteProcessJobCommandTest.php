<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Oro\Bundle\WorkflowBundle\Command\ExecuteProcessJobCommand;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Command\Stub\TestOutput;

class ExecuteProcessJobCommandTest extends \PHPUnit_Framework_TestCase
{
    const PROCESS_JOB_ENABLED   = 'enabled';
    const PROCESS_JOB_NOT_FOUND = 'not_found';

    /**
     * @var ExecuteProcessJobCommand
     */
    private $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $processHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $input;

    /**
     * @var TestOutput
     */
    private $output;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->processHandler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new ExecuteProcessJobCommand();
        $this->command->setContainer($this->container);

        $this->input   = $this->getMockForAbstractClass('Symfony\Component\Console\Input\InputInterface');
        $this->output = new TestOutput();
    }

    public function testConfigure()
    {
        $this->command->configure();

        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }

    /**
     * @param array $ids
     * @param array $expectedOutput
     * @param \Exception[] $exceptions
     * @dataProvider executeProvider
     */
    public function testExecute(array $ids, $expectedOutput, array $exceptions = [])
    {
        $this->expectContainerGetManagerRegistryAndProcessHandler();

        $successful = !count($exceptions);

        $this->input->expects($this->once())
            ->method('getOption')
            ->with('id')
            ->will($this->returnValue($ids));

        $processJobs = $this->populateProcessJobs($ids);

        $index = 0;

        foreach ($processJobs as $processJob) {
            $stub = $successful ? $this->returnSelf() : $this->throwException($exceptions[round($index / 2)]);
            $this->processHandler->expects($this->at($index++))
                ->method('handleJob')
                ->with($processJob)
                ->will($stub);
            $this->processHandler->expects($this->at($index++))
                ->method('finishJob')
                ->with($processJob);
        }

        if ($exceptions) {
            /** @var \Exception $exception */
            $exception = reset($exceptions);
            $this->setExpectedException(get_class($exception), $exception->getMessage());
        }

        $this->expectProcessJobRepositoryFind($ids, $processJobs);
        $this->expectProcessJobEntityManagerHandleJobs($successful, $processJobs);

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
     * @param array $ids
     * @return ProcessJob[]
     */
    protected function populateProcessJobs(array $ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $process = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessJob')
                ->disableOriginalConstructor()
                ->getMock();
            $process->expects($this->any())
                ->method('getId')
                ->will($this->returnValue($id));
            $definition = new ProcessDefinition();
            $definition->setName('name');
            $definition->setLabel('label');
            $processTrigger = new ProcessTrigger();
            $processTrigger->setDefinition($definition);
            $process->expects($this->any())
                ->method('getProcessTrigger')
                ->will($this->returnValue($processTrigger));
            $result[] = $process;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        return array(
            'single id' => array(
                'ids' => array(1),
                'output' => [
                    'Process job #1 name successfully finished'
                ],
            ),
            'several ids successful' => array(
                'ids' => array(1, 2, 3),
                'output' => [
                    'Process job #1 name successfully finished',
                    'Process job #2 name successfully finished',
                    'Process job #3 name successfully finished',
                ],
            ),
            'several ids failed' => array(
                'ids' => array(1, 2, 3),
                'output' => [
                    'Process job #1 name failed: Process 1 exception',
                    'Process job #2 name failed: Process 2 exception',
                    'Process job #3 name failed: Process 3 exception',
                ],
                'exceptions' => [
                    new \Exception('Process 1 exception'),
                    new \Exception('Process 2 exception'),
                    new \Exception('Process 3 exception'),
                ],
            ),
        );
    }

    public function testExecuteEmptyIdError()
    {
        $this->expectContainerGetManagerRegistryAndProcessHandler();

        $ids = array(1);
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('id')
            ->will($this->returnValue($ids));

        $this->expectProcessJobRepositoryFind($ids, [null]);
        $this->processHandler->expects($this->never())
            ->method($this->anything());

        $this->command->execute($this->input, $this->output);

        $this->assertAttributeEquals(
            ['Process job 1 does not exist'],
            'messages',
            $this->output
        );
    }

    public function testExecuteEmptyNoIdsSpecified()
    {
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('id')
            ->will($this->returnValue([]));

        $this->processHandler->expects($this->never())
            ->method($this->anything());

        $this->command->execute($this->input, $this->output);

        $this->assertAttributeEquals(
            ['No process identifiers defined'],
            'messages',
            $this->output
        );
    }

    /**
     * @param bool $successful
     * @param ProcessJob[] $processJobs
     */
    protected function expectProcessJobEntityManagerHandleJobs($successful, array $processJobs)
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry->expects($this->exactly(count($processJobs)))
            ->method('resetManager');
        $this->managerRegistry->expects($this->exactly(count($processJobs)))
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $index = 0;
        if ($successful) {
            foreach ($processJobs as $processJob) {
                $entityManager->expects($this->at($index++))
                    ->method('beginTransaction');

                $entityManager->expects($this->at($index++))
                    ->method('remove')
                    ->with($processJob);

                $entityManager->expects($this->at($index++))
                    ->method('flush');

                $entityManager->expects($this->at($index++))
                    ->method('clear');

                $entityManager->expects($this->at($index++))
                    ->method('commit');
            }
        } else {
            foreach ($processJobs as $processJob) {
                $entityManager->expects($this->at($index++))
                    ->method('beginTransaction');

                $entityManager->expects($this->at($index++))
                    ->method('clear');

                $entityManager->expects($this->at($index++))
                    ->method('rollback');
            }
        }
    }

    /**
     * @param array $processJobIds
     * @param ProcessJob[] $processJobs
     */
    protected function expectProcessJobRepositoryFind(array $processJobIds, array $processJobs)
    {
        $repository = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertSameSize($processJobIds, $processJobs);

        foreach ($processJobIds as $key => $processJobId) {
            $repository->expects($this->at($key))
                ->method('find')
                ->with($processJobId)
                ->will($this->returnValue($processJobs[$key]));
        }

        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with('OroWorkflowBundle:ProcessJob')
            ->will($this->returnValue($repository));
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
