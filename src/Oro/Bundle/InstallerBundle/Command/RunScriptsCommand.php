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
            ->setDescription('Run scripts.')
            ->addArgument(
                'script-files',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Script files'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = $input->getArgument('script-files');
        foreach ($paths as $path) {
            $this->getContainer()
                ->get('oro_installer.installer_provider')
                ->runFile($path, $output, $this->getContainer());
            $output->writeln(sprintf('%s has run!', $path));
        }
    }
}
