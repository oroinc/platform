<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Command;

use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Universal command for asynchronously fixing data after upgrade
 */
#[AsCommand(
    name: 'oro:platform:post-upgrade-tasks',
    description: 'Schedules background jobs to fix data asynchronously after upgrade.'
)]
class PostUpgradeTasksCommand extends Command
{
    private const DEFAULT_BATCH_SIZE = 500;

    public function __construct(
        private PostUpgradeTaskRegistry $taskRegistry
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'task',
                't',
                InputOption::VALUE_OPTIONAL,
                'Specific task name to execute (e.g., product_fallback)'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Execute all available tasks'
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Number of items to process in a single batch',
                self::DEFAULT_BATCH_SIZE
            )
            ->addOption(
                'list',
                'l',
                InputOption::VALUE_NONE,
                'List all available tasks'
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Option --list: show all available tasks
        if ($input->getOption('list')) {
            return $this->listTasks($io);
        }

        $batchSize = (int) $input->getOption('batch-size');
        if ($batchSize <= 0) {
            $io->error('The batch size must be a positive integer.');
            return Command::INVALID;
        }

        // Option --all: execute all tasks
        if ($input->getOption('all')) {
            return $this->executeAllTasks($input, $output, $io);
        }

        // Option --task: execute specific task
        $taskName = $input->getOption('task');
        if ($taskName) {
            return $this->executeTask($input, $output, $io, $taskName);
        }

        // If no options specified - show help
        $io->error('Please specify --task=NAME, --all, or --list option.');
        $io->note('Use --list to see all available tasks.');

        return Command::INVALID;
    }

    private function listTasks(SymfonyStyle $io): int
    {
        $tasks = $this->taskRegistry->getAllTasks();

        if (empty($tasks)) {
            $io->warning('No post-upgrade tasks are registered.');
            return Command::SUCCESS;
        }

        $io->title('Available Post-Upgrade Tasks');

        $rows = [];
        foreach ($tasks as $task) {
            $rows[] = [$task->getName(), $task->getDescription()];
        }

        $io->table(['Task Name', 'Description'], $rows);

        $io->note(sprintf(
            'Execute a specific task: %s --task=<NAME>',
            $this->getName()
        ));
        $io->note(sprintf(
            'Execute all tasks: %s --all',
            $this->getName()
        ));

        return Command::SUCCESS;
    }

    private function executeAllTasks(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $tasks = $this->taskRegistry->getAllTasks();

        if (empty($tasks)) {
            $io->warning('No post-upgrade tasks are registered.');
            return Command::SUCCESS;
        }

        $io->title('Executing All Post-Upgrade Tasks');

        $totalScheduled = 0;
        $executedCount = 0;

        foreach ($tasks as $task) {
            $result = $task->execute($input, $output, $io);

            if ($result->isExecuted()) {
                $executedCount++;
                if ($result->getScheduledCount() !== null) {
                    $totalScheduled += $result->getScheduledCount();
                }
                $io->success(sprintf(
                    '[%s] %s',
                    $result->getTaskName(),
                    $result->getMessage() ?? 'Task executed successfully.'
                ));
            } else {
                $io->info(sprintf(
                    '[%s] %s',
                    $result->getTaskName(),
                    $result->getMessage() ?? 'Task skipped (no work needed).'
                ));
            }
        }

        $io->newLine();
        if ($totalScheduled > 0) {
            $io->writeln(sprintf(
                '<info>Summary:</info> Executed %d of %d tasks, scheduled %d items total.',
                $executedCount,
                count($tasks),
                $totalScheduled
            ));
        } else {
            $io->writeln(sprintf(
                '<info>Summary:</info> Executed %d of %d tasks.',
                $executedCount,
                count($tasks)
            ));
        }

        return Command::SUCCESS;
    }

    private function executeTask(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        string $taskName
    ): int {
        $task = $this->taskRegistry->getTaskByName($taskName);

        if (!$task) {
            $io->error(sprintf('Task "%s" not found.', $taskName));
            $io->note('Use --list to see all available tasks.');
            return Command::FAILURE;
        }

        $io->title(sprintf('Executing Task: %s', $taskName));
        $io->writeln(sprintf('<comment>%s</comment>', $task->getDescription()));
        $io->newLine();

        $result = $task->execute($input, $output, $io);

        if ($result->isExecuted()) {
            $io->success($result->getMessage() ?? 'Task executed successfully.');
        } else {
            $io->info($result->getMessage() ?? 'Task skipped (no work needed).');
        }

        return Command::SUCCESS;
    }
}
