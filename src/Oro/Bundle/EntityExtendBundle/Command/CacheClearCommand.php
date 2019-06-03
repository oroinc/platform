<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears extended entity cache
 */
class CacheClearCommand extends CacheCommand
{
    /** @var string */
    protected static $defaultName = 'oro:entity-extend:cache:clear';

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setDescription('Clears extended entity cache.')
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Clear extended entity cache');

        $this->extendConfigDumper->clear();

        if (!$input->getOption('no-warmup')) {
            $this->warmup($output);
        }
    }
}
