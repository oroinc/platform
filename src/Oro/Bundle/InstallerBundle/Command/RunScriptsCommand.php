<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class RunScriptsCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:platform:run-script')
            ->setDescription('Run php script files.')
            ->addArgument(
                'scripts',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Script files'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = $input->getArgument('scripts');
        foreach ($paths as $path) {
            $this->getContainer()
                ->get('oro_installer.script_manager')
                ->runScript($path, $output, $this->getContainer());
            $output->writeln(sprintf('%s has run!', $path));
        }
    }
}
