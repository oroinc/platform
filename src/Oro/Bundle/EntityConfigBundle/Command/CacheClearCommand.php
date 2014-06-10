<?php

namespace Oro\Bundle\EntityConfigBundle\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClearCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-config:cache:clear')
            ->setDescription('Clears the entity config cache.')
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Clear the entity config cache');

        $this->getConfigManager()->clearCache();
        $this->getConfigManager()->clearConfigurableCache();

        if (!$input->getOption('no-warmup')) {
            // @todo: add the warming up of the entity config cache here
        }
    }
}
