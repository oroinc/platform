<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Command\WorkflowTransitCommand;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;

class WorkflowTransitCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var Input|\PHPUnit\Framework\MockObject\MockObject */
    private $input;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repo;

    /** @var OutputStub */
    private $output;

    /** @var WorkflowTransitCommand */
    private $command;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(EntityRepository::class);

        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with(WorkflowItem::class)
            ->willReturn($this->repo);

        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->command = new WorkflowTransitCommand($this->managerRegistry, $this->workflowManager);

        $this->input = $this->createMock(InputInterface::class);
        $this->output = new OutputStub();
    }

    public function testConfigure()
    {
        $this->command->configure();

        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }

    /**
     * @dataProvider executeProvider
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testExecute(
        string|int $id,
        ?string $transition,
        array $expectedOutput,
        \Exception $exception = null,
        \Exception $expectedException = null
    ) {
        $this->input->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['workflow-item', $id],
                ['transition', $transition],
            ]);

        $workflowItem = $this->createWorkflowItem($id);

        if (!$transition || !is_numeric($id)) {
            $this->repo->expects($this->never())
                ->method('find');
        } else {
            $this->repo->expects($this->once())
                ->method('find')
                ->with($id)
                ->willReturn($workflowItem);
        }

        if ((!$workflowItem) || (!$transition)) {
            $this->workflowManager->expects($this->never())
                ->method('transit');
        } elseif ($exception) {
            $this->workflowManager->expects($this->once())
                ->method('transit')
                ->with($workflowItem, $transition)
                ->willThrowException($exception);
        } else {
            $this->workflowManager->expects($this->once())
                ->method('transit')
                ->with($workflowItem, $transition)
                ->willReturnSelf();
        }

        if ($expectedException) {
            $this->expectException(get_class($expectedException));
            $this->expectExceptionMessage($expectedException->getMessage());
        }

        $this->command->execute($this->input, $this->output);

        $found = 0;
        foreach ($this->output->messages as $message) {
            foreach ($expectedOutput as $expected) {
                if (str_contains($message, $expected)) {
                    $found++;
                }
            }
        }

        $this->assertCount($found, $expectedOutput);
    }

    public function executeProvider(): array
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
                'expectedException' => new \RuntimeException('Transition 1 exception'),
            ],
            'no workflow item' => [
                'id' => 99,
                'name' => 'transit',
                'output' => [
                    'Start transition...',
                    'Exception: Workflow Item not found',
                ],
                'exception' => new \RuntimeException('Workflow Item not found'),
                'expectedException' => new \RuntimeException('Workflow Item not found'),
            ],
            'no transition' => [
                'id' => 2,
                'name' => null,
                'output' => [
                    'No Transition name defined',
                ],
                'exception' => new \RuntimeException('No Transition name defined'),
                'expectedException' => new \RuntimeException('No Transition name defined'),
            ],
            'wrong workflow item id' => [
                'id' => 'item_id',
                'name' => null,
                'output' => [
                    'No Workflow Item identifier defined',
                ],
                'exception' => new \RuntimeException('No Workflow Item identifier defined'),
                'expectedException' => new \RuntimeException('No Workflow Item identifier defined'),
            ],
            'transition not allowed' => [
                'id' => 2,
                'name' => 'transit',
                'output' => [
                    'Start transition...',
                    'Transition "transit" is not allowed.',
                ],
                'exception' => new ForbiddenTransitionException('Transition "transit" is not allowed.'),
            ],
        ];
    }

    private function createWorkflowItem(string|int $id): ?WorkflowItem
    {
        if ($id > 2) {
            return null;
        }

        $workflowItem = new WorkflowItem();
        $workflowItem->setId($id);

        return $workflowItem;
    }
}
