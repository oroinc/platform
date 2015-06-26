<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheWarmupCommand extends CacheCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-extend:cache:warmup')
            ->setDescription('Warms up extended entity cache.')
            ->addOption(
                'cache-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'The cache directory'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Warm up extended entity cache.');

        $this->cacheDir = $input->getOption('cache-dir');

        $this->warmup($output);
    }
}
