<?php
declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Command;

use Oro\Bundle\InstallerBundle\CommandExecutor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for install and update commands that need to pass debug and timeout options to subsequent commands.
 */
abstract class AbstractCommand extends Command
{
    protected const DEFAULT_TIMEOUT = 3600;

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption('force-debug', null, InputOption::VALUE_NONE, 'Launch child commands in debug mode.')
            ->addOption(
                'timeout',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum execution time (in seconds) of child commands',
                self::DEFAULT_TIMEOUT
            )
            ->setHelp(
                $this->getHelp() . <<<'HELP'

The <info>--force-debug</info> option will launch the child commands in the debug mode
(be default they are launched with --no-debug):

  <info>php %command.full_name% --force-debug</info> <fg=green;options=underscore>other options</>

The <info>--timeout</info> option can be used to limit execution time of the child commands:

  <info>php %command.full_name% --timeout=<seconds></info> <fg=green;options=underscore>other options</>

HELP
            )
            ->addUsage('--force-debug [other options]')
            ->addUsage('--timeout=<seconds> [other options]')
        ;
    }

    protected function getCommandExecutor(InputInterface $input, OutputInterface $output): CommandExecutor
    {
        $container = $this->getContainer();

        $commandExecutor = new CommandExecutor(
            $input->hasOption('env') ? $input->getOption('env') : null,
            $output,
            $this->getApplication(),
            $container->get('oro_cache.oro_data_cache_manager')
        );

        $timeout = $input->getOption('timeout');
        if ($timeout >= 0) {
            $commandExecutor->setDefaultOption('process-timeout', $timeout);
        }

        if (!$input->getOption('force-debug')
            && (true === $input->getOption('no-debug') || $container->get('kernel')->isDebug())
        ) {
            $commandExecutor->setDefaultOption('no-debug');
        }

        return $commandExecutor;
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
