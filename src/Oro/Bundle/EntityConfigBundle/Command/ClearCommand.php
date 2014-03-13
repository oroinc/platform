<?php

namespace Oro\Bundle\EntityConfigBundle\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-config:clear')
            ->setDescription('Clears entity config cache.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Clear entity config cache');

        $this->getConfigManager()->clearCache();
        $this->getConfigManager()->clearConfigurableCache();
    }
}
