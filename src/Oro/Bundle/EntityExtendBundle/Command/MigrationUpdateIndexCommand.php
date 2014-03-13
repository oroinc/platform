<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationUpdateIndexCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-extend:migration:update-index')
            ->setDescription(
                'Updates extended entities indexes during a database structure migration process.'
                . ' This is an internal command. Please do not run it manually.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Outputs modifications without apply them'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {

    }
}
