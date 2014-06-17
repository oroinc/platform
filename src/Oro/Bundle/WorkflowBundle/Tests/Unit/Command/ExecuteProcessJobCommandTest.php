<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Oro\Bundle\WorkflowBundle\Command\ExecuteProcessJobCommand;

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
    private $input;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $output;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $this->command = new ExecuteProcessJobCommand();
        $this->command->setContainer($this->container);

        $this->input   = $this->getMockForAbstractClass('Symfony\Component\Console\Input\InputInterface');
        $this->output = $this->getMockForAbstractClass('Symfony\Component\Console\Output\OutputInterface');
    }

    public function testConfigure()
    {
        $this->command->configure();

        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }

    public function testExecute()
    {
        $processJobId = 1;
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('id')
            ->will($this->returnValue($processJobId));

        $this->output->expects($this->never())
            ->method('writeln');

        $processHandler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $processJob = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessJob')
            ->disableOriginalConstructor()
            ->getMock();

        $processHandler->expects($this->once())
            ->method('handleJob')
            ->with($processJob)
            ->will($this->returnSelf());

        $this->container->expects($this->at(1))
            ->method('get')
            ->with('oro_workflow.process.process_handler')
            ->will($this->returnValue($processHandler));

        $this->assetProcessJobRepository($processJobId, $processJob);

        $this->command->execute($this->input, $this->output);
    }

    public function testExecuteEmptyIdError()
    {
        $id = 1;
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('id')
            ->will($this->returnValue($id));

        $this->output->expects($this->once())
            ->method('writeln')
            ->with(sprintf('<error>Process job with passed identity "%s" does not exist.</error>', $id))
            ->will($this->returnSelf());

        $this->assetProcessJobRepository($id, null);

        $this->command->execute($this->input, $this->output);
    }

    protected function assetProcessJobRepository($processJobId, $processJob, $callOrder = 0)
    {
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->with($processJobId)
            ->will($this->returnValue($processJob));

        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $registry->expects($this->once())
            ->method('getRepository')
            ->with('OroWorkflowBundle:ProcessJob')
            ->will($this->returnValue($repository));

        if ($processJob) {
            $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();
            $entityManager->expects($this->once())
                ->method('remove')
                ->with($processJob)
                ->will($this->returnSelf());
            $entityManager->expects($this->once())
                ->method('flush')
                ->will($this->returnSelf());
            $registry->expects($this->once())
                ->method('getManagerForClass')
                ->with('OroWorkflowBundle:ProcessJob')
                ->will($this->returnValue($entityManager));
        }

        $this->container->expects($this->at($callOrder))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($registry));
    }
}
