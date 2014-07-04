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

    /**
     * @param array $ids
     * @dataProvider executeProvider
     */
    public function testExecute($ids)
    {
        $callCount = count($ids);
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('id')
            ->will($this->returnValue($ids));

        $this->output->expects($this->exactly($callCount))
            ->method('writeln');

        $processHandler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $processJob = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessJob')
            ->disableOriginalConstructor()
            ->getMock();

        $processHandler->expects($this->exactly($callCount))
            ->method('handleJob')
            ->with($processJob)
            ->will($this->returnSelf());

        $this->container->expects($this->at(1))
            ->method('get')
            ->with('oro_workflow.process.process_handler')
            ->will($this->returnValue($processHandler));

        $this->assetProcessJobRepository($ids, array_fill(0, $callCount, $processJob));

        $this->command->execute($this->input, $this->output);
    }

    public function executeProvider()
    {
        return array(
            'single id'   => array('ids' => array(1)),
            'several ids' => array('ids' => array(1, 2, 3, 4, 5))
        );
    }

    public function testExecuteEmptyIdError()
    {
        $ids = array(1);
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('id')
            ->will($this->returnValue($ids));

        $this->output->expects($this->once())
            ->method('writeln')
            ->with(sprintf('<error>Process jobs with passed identities does not exist.</error>'))
            ->will($this->returnSelf());

        $this->assetProcessJobRepository($ids, null);

        $this->command->execute($this->input, $this->output);
    }

    protected function assetProcessJobRepository($processJobIds, $processJobs, $callOrder = 0)
    {
        $repository = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findByIds')
            ->with($processJobIds)
            ->will($this->returnValue($processJobs));

        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $registry->expects($this->once())
            ->method('getRepository')
            ->with('OroWorkflowBundle:ProcessJob')
            ->will($this->returnValue($repository));

        if ($processJobs) {
            $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();
            $entityManager->expects($this->exactly(count($processJobs)))
                ->method('remove')
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
