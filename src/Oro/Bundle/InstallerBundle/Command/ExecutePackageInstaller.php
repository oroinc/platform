<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecutePackageInstaller extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:package:execute-install')
            ->setDescription('Install packages.')
            ->addArgument(
                'package-install-file',
                InputArgument::REQUIRED,
                'Package install file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('package-install-file');

        $installProvider = $this->getContainer()->get('oro_installer.installer_provider');
        $installProvider->runFile($path, $output, $this->getContainer());
    }
}
