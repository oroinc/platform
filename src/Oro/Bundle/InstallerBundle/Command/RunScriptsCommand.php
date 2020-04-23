<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\ScriptExecutor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The CLI command to run PHP script files in scope application container.
 */
class RunScriptsCommand extends ContainerAwareCommand
{
    /** @var string */
    protected static $defaultName = 'oro:platform:run-script';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Run PHP script files in scope application container.')
            ->addArgument(
                'script',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Script files'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandExecutor = new CommandExecutor(
            $input->hasOption('env') ? $input->getOption('env') : null,
            $output,
            $this->getApplication(),
            $this->getContainer()->get('oro_cache.oro_data_cache_manager')
        );
        $scriptExecutor = new ScriptExecutor(
            $output,
            $this->getContainer(),
            $commandExecutor
        );
        $scriptFiles = $input->getArgument('script');
        foreach ($scriptFiles as $scriptFile) {
            $scriptExecutor->runScript($scriptFile);
        }
    }
}
