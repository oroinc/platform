<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Command\OroTranslationLoadCommand;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads workflow definitions to the database.
 */
class LoadWorkflowDefinitionsCommand extends Command
{
    private const DEFAULT_WATCH_INTERVAL = 10;

    /** @var string */
    protected static $defaultName = 'oro:workflow:definitions:load';

    private WorkflowConfigurationProvider $configurationProvider;
    private WorkflowDefinitionHandler $definitionHandler;
    private WorkflowDefinitionConfigurationBuilder $configurationBuilder;
    private ManagerRegistry $doctrine;

    public function __construct(
        WorkflowConfigurationProvider $configurationProvider,
        WorkflowDefinitionHandler $definitionHandler,
        WorkflowDefinitionConfigurationBuilder $configurationBuilder,
        ManagerRegistry $doctrine
    ) {
        parent::__construct();
        $this->configurationProvider = $configurationProvider;
        $this->definitionHandler = $definitionHandler;
        $this->configurationBuilder = $configurationBuilder;
        $this->doctrine = $doctrine;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption(
                'directories',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Directories with workflow configurations'
            )
            ->addOption(
                'workflows',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Workflow names'
            )
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Continue to watch for changes after initial load')
            ->addOption(
                'watch-interval',
                null,
                InputOption::VALUE_OPTIONAL,
                'Definition reload interval seconds',
                self::DEFAULT_WATCH_INTERVAL
            )
            ->setDescription('Loads workflow definitions to the database.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command loads workflow definitions
from configuration files to the database.

  <info>php %command.full_name%</info>

The <info>--directories</info> option can be used to specify custom location(s)
of the workflow configuration files:

  <info>php %command.full_name% --directories=<path1> --directories=<path2></info>

The <info>--workflows</info> option can be used to load only the specified workflows:

  <info>php %command.full_name% --workflows=<workflow1> --workflows=<workflow2></info>

The <info>--watch</info> option can be used to load workflow definitions periodically during development. 
The reload period is defined by the <info>--watch-interval</info> option:

  <info>php %command.full_name% --watch --watch-interval=<interval></info>
  
It is highly recommended to use the <info>--watch</info> option with the <info>--workflows</info> option
for performance reasons.

HELP
            )
            ->addUsage('--directories=<path1> --directories=<path2>')
            ->addUsage('--workflows=<workflow1> --workflows=<workflow2>')
            ->addUsage('--workflows=<workflow1> --watch --watch-interval=<interval>');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $usedDirectories = $input->getOption('directories') ?: null;
        $usedWorkflows = $input->getOption('workflows') ?: null;

        $hasDefinitions = $this->doLoad($usedDirectories, $usedWorkflows, $output);

        if ($input->getOption('watch')) {
            $watchInterval = $this->getWatchInterval($input);
            while (true) {
                sleep($watchInterval);
                $this->doLoad($usedDirectories, $usedWorkflows, $output);
            }
        }

        if ($hasDefinitions) {
            $output->writeln('Done.');
            $output->writeln('');
            $output->writeln(
                \sprintf(
                    "Please run command '<info>%s</info>' to load translations.",
                    OroTranslationLoadCommand::getDefaultName()
                )
            );
        } else {
            $output->writeln('No workflow definitions found.');
        }

        return Command::SUCCESS;
    }

    private function doLoad(?array $usedDirectories, ?array $usedWorkflows, OutputInterface $output): bool
    {
        $workflowConfiguration = $this->configurationProvider->getWorkflowDefinitionConfiguration(
            $usedDirectories,
            $usedWorkflows
        );

        if (!$workflowConfiguration) {
            return false;
        }
        $output->writeln('Loading workflow definitions...');

        /** @var WorkflowDefinitionRepository $workflowDefinitionRepository */
        $workflowDefinitionRepository = $this->doctrine->getRepository(WorkflowDefinition::class);
        $workflowDefinitions = $this->configurationBuilder->buildFromConfiguration($workflowConfiguration);

        $countProcessed = 0;
        $countCreated = 0;
        $countUpdated = 0;
        foreach ($workflowDefinitions as $workflowDefinition) {
            $countProcessed++;
            $output->writeln(
                \sprintf('  <comment>></comment> <info>%s</info>', $workflowDefinition->getName()),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            // all loaded workflows set as system by default
            $workflowDefinition->setSystem(true);

            /** @var WorkflowDefinition $existingWorkflowDefinition */
            $existingWorkflowDefinition = $workflowDefinitionRepository->find($workflowDefinition->getName());

            if ($existingWorkflowDefinition) {
                $this->definitionHandler->updateWorkflowDefinition(
                    $existingWorkflowDefinition,
                    $workflowDefinition
                );
                $countUpdated++;
            } else {
                $this->definitionHandler->createWorkflowDefinition($workflowDefinition);
                $countCreated++;
            }

            if ($output->isDebug()) {
                $output->writeln(Yaml::dump($workflowDefinition->getConfiguration(), 10));
            }
        }
        $output->writeln(
            \sprintf(
                'Processed %d workflow definitions: updated %d existing workflows, created %d new workflows.',
                $countProcessed,
                $countUpdated,
                $countCreated
            ),
            OutputInterface::VERBOSITY_VERBOSE
        );

        return true;
    }

    private function getWatchInterval(InputInterface $input): int
    {
        $watchInterval = (int)$input->getOption('watch-interval');
        if ($watchInterval <= 0) {
            $watchInterval = self::DEFAULT_WATCH_INTERVAL;
        }

        return $watchInterval;
    }
}
