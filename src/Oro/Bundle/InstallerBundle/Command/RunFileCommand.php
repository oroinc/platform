<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunFileCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:package:run-file')
            ->setDescription('Run file.')
            ->addArgument(
                'package-file',
                InputArgument::REQUIRED,
                'Package file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('package-install-file');
        $installProvider = $this->getContainer()->get('oro_installer.installer_provider');
        $installProvider->runFile($path, $output, $this->getContainer());
        $output->writeln(sprintf('%s has run!', $path));
    }
}
