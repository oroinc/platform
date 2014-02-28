<?php

namespace Oro\Bundle\EntityConfigBundle\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends BaseCommand
{
    public function configure()
    {
        $this
            ->setName('oro:entity-config:clear')
            ->setDescription('Clear config cache');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getDescription());
        $this->getConfigManager()->clearCacheAll();
    }
}
