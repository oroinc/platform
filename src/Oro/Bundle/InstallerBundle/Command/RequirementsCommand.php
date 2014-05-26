<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RequirementsCommand
 *
 * Empty command to check cli requirements with web installer
 */
class RequirementsCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:platform:check-requirements')
            ->setDescription('Empty command to check requirements');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
