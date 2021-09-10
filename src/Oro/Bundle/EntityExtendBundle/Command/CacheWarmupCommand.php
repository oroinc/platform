<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Warms up extended entity cache.
 */
class CacheWarmupCommand extends CacheCommand
{
    /** @var string */
    protected static $defaultName = 'oro:entity-extend:cache:warmup';

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('cache-dir', null, InputOption::VALUE_REQUIRED, 'Cache directory')
            ->setDescription('Warms up extended entity cache.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command warms up extended entity cache and
its related caches (Doctrine metadata, Doctrine proxy classes for extended entities,
cache of entity aliases).

  <info>php %command.full_name%</info>

The <info>--cache-dir</info> option can be used to override the default cache directory location.

  <info>php %command.full_name% --cache-dir=<path></info>

HELP
            )
            ->addUsage('--cache-dir=<path>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Warm up extended entity cache.');

        $this->cacheDir = $input->getOption('cache-dir');

        $this->warmup($output);

        return 0;
    }
}
