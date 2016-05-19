<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Console\Input\Input;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\WorkflowBundle\Command\ExecuteWorkflowTransitionCommand;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Command\Stub\TestOutput;

class ExecuteWorkflowTransitionCommandTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'OroWorkflowBundle:WorkflowItem';

    /** @var ExecuteWorkflowTransitionCommand */
    private $command;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface */
    private $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    private $managerRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|WorkflowManager */
    private $workflowManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Input */
    private $input;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository */
    private $repo;

    /** @var TestOutput */
    private $output;

    protected function setUp()
    {
        $this->repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->with(self::CLASS_NAME)
            ->willReturn($this->repo);

        $this->managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::CLASS_NAME)
            ->willReturn($em);

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new ExecuteWorkflowTransitionCommand();
        $this->command->setContainer($this->container);

        $this->input = $this->getMockForAbstractClass('Symfony\Component\Console\Input\InputInterface');
        $this->output = new TestOutput();
    }

    protected function tearDown()
    {
        unset(
            $this->container,
            $this->repo,
            $this->workflowManager,
            $this->managerRegistry,
            $this->input,
            $this->output,
            $this->command
        );
    }

    public function testConfigure()
    {
        $this->command->configure();

        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }

    /**
     * @param int $id
     * @param string $transition
     * @param array $expectedOutput
     * @param \Exception $exception
     * @dataProvider executeProvider
     */
    public function testExecute($id, $transition, $expectedOutput, \Exception $exception = null)
    {
        $this->expectContainerGetManagerRegistryAndWorkflowManager();
        $this->input->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['workflow-item', $id],
                ['transition', $transition],
            ]);

        $workflowItem = $this->createWorkflowItem($id);

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($workflowItem);

        $this->workflowManager->expects($this->once())
            ->method('transit')
            ->with($workflowItem, $transition)
            ->will($exception ? $this->throwException($exception) : $this->returnSelf());

        if ($exception) {
            $this->setExpectedException(get_class($exception), $exception->getMessage());
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
     * @return array
     */
    public function executeProvider()
    {
        return [
            'valid id' => [
                'id' => 1,
                'name' => 'transit',
                'output' => [
                    'Start transition...',
                    'successfully finished',
                ],
            ],
            'wrong id' => [
                'id' => 2,
                'name' => 'transit',
                'output' => [
                    'Start transition...',
                    'Transition #transit failed: Transition 1 exception',
                ],
                'exception' => new \RuntimeException('Transition 1 exception'),
            ],
        ];
    }

    public function testExecuteNoWorkflowItemError()
    {
        $this->expectContainerGetManagerRegistryAndWorkflowManager();

        $this->input->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['workflow-item', 1],
                ['transition', 'transit'],
            ]);

        $this->workflowManager->expects($this->never())->method($this->anything());

        $this->command->execute($this->input, $this->output);

        $this->assertAttributeEquals(['Workflow Item not found'], 'messages', $this->output);
    }

    public function testExecuteEmptyWrongIdSpecified()
    {
        $this->input->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['workflow-item', '123a'],
                ['transition', 'transit'],
            ]);

        $this->workflowManager->expects($this->never())->method($this->anything());

        $this->command->execute($this->input, $this->output);

        $this->assertAttributeEquals(['No Workflow Item identifier defined'], 'messages', $this->output);
    }

    public function testExecuteNoTransitionNameError()
    {
        $this->input->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['workflow-item', 1],
            ]);

        $this->workflowManager->expects($this->never())->method($this->anything());

        $this->command->execute($this->input, $this->output);

        $this->assertAttributeEquals(['No Transition name defined'], 'messages', $this->output);
    }

    protected function expectContainerGetManagerRegistryAndWorkflowManager()
    {
        $this->container->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['oro_workflow.manager', 1, $this->workflowManager],
                ['oro_workflow.workflow_item.entity.class', 1, self::CLASS_NAME],
                ['doctrine', 1, $this->managerRegistry],
            ]);
    }

    /**
     * @param int $id
     * @return WorkflowItem
     */
    protected function createWorkflowItem($id)
    {
        $workflowItem = new WorkflowItem();
        $workflowItem
            ->setId($id);

        return $workflowItem;
    }
}
