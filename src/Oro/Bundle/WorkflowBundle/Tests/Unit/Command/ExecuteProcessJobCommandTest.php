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

        $processJob = $this->assetProcessEnabled(false);

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

    /**
     * @dataProvider executeErrorProvider
     */
    public function testExecuteEmptyIdError($id, $state, $message)
    {
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('id')
            ->will($this->returnValue($id));

        $this->output->expects($this->once())
            ->method('writeln')
            ->with($message)
            ->will($this->returnSelf());

        if (!$id) {
            $this->container->expects($this->never())
                ->method('get');
        } else {
            if (self::PROCESS_JOB_NOT_FOUND === $state) {
                $processJob = null;
            } else {
                $processJob = $this->assetProcessEnabled(true);
            }

            $this->assetProcessJobRepository($id, $processJob);
        }

        $this->command->execute($this->input, $this->output);
    }

    public function executeErrorProvider()
    {
        $processId = 1;
        return array(
            'empty ID' => array(
                'id'      => null,
                'state'   => self::PROCESS_JOB_ENABLED,
                'message' => '<error>Process job id is required. Please enter --id=<process job identity></error>'
            ),
            'job not found' => array(
                'id'      => $processId,
                'state'   => self::PROCESS_JOB_NOT_FOUND,
                'message' => '<error>Process job with passed identity "' . $processId . '" does not exist.</error>'
            ),
            'job already enabled' => array(
                'id'      => $processId,
                'state'   => self::PROCESS_JOB_ENABLED,
                'message' => '<error>Process job with passed identity "' . $processId . '" already enabled.</error>'
            )
        );
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

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroWorkflowBundle:ProcessJob')
            ->will($this->returnValue($repository));

        $this->container->expects($this->at($callOrder))
            ->method('get')
            ->with('doctrine.orm.default_entity_manager')
            ->will($this->returnValue($entityManager));
    }

    protected function assetProcessEnabled($isEnabled)
    {
        $processDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $processDefinition->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue($isEnabled));

        $processTrigger = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger')
            ->disableOriginalConstructor()
            ->getMock();
        $processTrigger->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($processDefinition));

        $processJob = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessJob')
            ->disableOriginalConstructor()
            ->getMock();
        $processJob->expects($this->once())
            ->method('getProcessTrigger')
            ->will($this->returnValue($processTrigger));

        return $processJob;
    }
}
