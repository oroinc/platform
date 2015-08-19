<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\InstallerBundle\CommandExecutor;

abstract class AbstractCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption(
                'force-debug',
                null,
                InputOption::VALUE_NONE,
                'Forces launching of child commands in debug mode. By default they are launched with --no-debug'
            )
            ->addOption(
                'timeout',
                null,
                InputOption::VALUE_OPTIONAL,
                'Execution timeout for child commands',
                CommandExecutor::DEFAULT_TIMEOUT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return CommandExecutor
     */
    protected function getCommandExecutor(InputInterface $input, OutputInterface $output)
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
}
