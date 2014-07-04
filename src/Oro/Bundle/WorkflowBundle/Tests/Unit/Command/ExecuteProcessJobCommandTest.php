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
     * @param bool $successful
     * @dataProvider executeProvider
     */
    public function testExecute(array $ids, $successful = true)
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

        if ($successful) {
            $processHandler->expects($this->exactly($callCount))
                ->method('handleJob')
                ->with($processJob)
                ->will($this->returnSelf());
        } else {
            $processHandler->expects($this->exactly($callCount))
                ->method('handleJob')
                ->with($processJob)
                ->will($this->throwException(new \Exception()));
        }

        $this->container->expects($this->at(1))
            ->method('get')
            ->with('oro_workflow.process.process_handler')
            ->will($this->returnValue($processHandler));

        $this->assertProcessJobRepository($ids, $successful, array_fill(0, $callCount, $processJob));

        $this->command->execute($this->input, $this->output);
    }

    public function executeProvider()
    {
        return array(
            'single id' => array('ids' => array(1)),
            'several ids successful' => array('ids' => array(1, 2, 3, 4, 5)),
            'several ids failed' => array('ids' => array(1, 2, 3, 4, 5), 'successful' => false),
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
            ->with(sprintf('<error>Process jobs with passed identifiers do not exist</error>'))
            ->will($this->returnSelf());

        $processJob = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessJob')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertProcessJobRepository($ids, true, array());

        $this->command->execute($this->input, $this->output);
    }

    /**
     * @param array $processJobIds
     * @param bool $successful
     * @param array $processJobs
     * @param int $callOrder
     */
    protected function assertProcessJobRepository(array $processJobIds, $successful, array $processJobs, $callOrder = 0)
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

            $entityManager->expects($this->exactly(count($processJobs)))->method('beginTransaction');

            if ($successful) {
                $entityManager->expects($this->exactly(count($processJobs)))->method('remove');
                $entityManager->expects($this->exactly(count($processJobs)))->method('flush');
                $entityManager->expects($this->exactly(count($processJobs)))->method('commit');
            } else {
                $entityManager->expects($this->exactly(count($processJobs)))->method('rollback');
            }

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
