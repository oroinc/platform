<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;

class HandleProcessTriggerCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $processHandler;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repo;

    /** @var Input|\PHPUnit\Framework\MockObject\MockObject */
    private $input;

    /** @var OutputStub */
    private $output;

    /** @var HandleProcessTriggerCommand */
    private $command;

    protected function setUp(): void
    {
        $this->processHandler = $this->createMock(ProcessHandler::class);
        $this->repo = $this->createMock(EntityRepository::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = new OutputStub();

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with('OroWorkflowBundle:ProcessTrigger')
            ->willReturn($this->repo);
        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->createMock(EntityManagerInterface::class));

        $this->command = new HandleProcessTriggerCommand($doctrine, $this->processHandler);
    }

    public function testConfigure()
    {
        $this->command->configure();

        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }

    /**
     * @dataProvider executeProvider
     */
    public function testExecute(int $id, array $expectedOutput, \Exception $exception = null)
    {
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

        if ($exception) {
            $this->processHandler->expects($this->once())
                ->method('handleTrigger')
                ->with($processTrigger, $processData)
                ->willThrowException($exception);
        } else {
            $this->processHandler->expects($this->once())
                ->method('handleTrigger')
                ->with($processTrigger, $processData)
                ->willReturnSelf();
        }

        $this->processHandler->expects($this->once())
            ->method('finishTrigger')
            ->with($processTrigger, $processData);

        if ($exception) {
            $this->expectException(get_class($exception));
            $this->expectExceptionMessage($exception->getMessage());
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

    private function createProcessTrigger(int $id): ProcessTrigger
    {
        $definition = (new ProcessDefinition())
            ->setName('name')
            ->setLabel('label')
            ->setRelatedEntity(\stdClass::class);

        $processTrigger = new ProcessTrigger();
        ReflectionUtil::setId($processTrigger, $id);
        $processTrigger->setDefinition($definition);

        return $processTrigger;
    }

    public function executeProvider(): array
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
        $this->input->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['id', 1],
                ['name', 'name'],
            ]);

        $this->processHandler->expects($this->never())
            ->method($this->anything());

        $this->command->execute($this->input, $this->output);

        self::assertEquals("Process trigger not found\n", $this->output->getOutput());
    }

    public function testExecuteEmptyNoIdSpecified()
    {
        $this->input->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['id', '123a'],
                ['name', 'name'],
            ]);

        $this->processHandler->expects($this->never())
            ->method($this->anything());

        $this->command->execute($this->input, $this->output);

        self::assertEquals("No process trigger identifier defined\n", $this->output->getOutput());
    }

    public function testExecuteWrongNameSpecified()
    {
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

        $this->processHandler->expects($this->never())
            ->method($this->anything());

        $this->command->execute($this->input, $this->output);

        self::assertEquals('Trigger not found in process definition "wrong_name"' . "\n", $this->output->getOutput());
    }
}
