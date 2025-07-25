<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Warms up extended entity cache.
 */
#[AsCommand(
    name: 'oro:entity-extend:cache:warmup',
    description: 'Warms up extended entity cache.'
)]
class CacheWarmupCommand extends CacheCommand
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    public function configure()
    {
        $this
            ->addOption('cache-dir', null, InputOption::VALUE_REQUIRED, 'Cache directory')
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
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Warm up extended entity cache.');

        $this->cacheDir = $input->getOption('cache-dir');

        $this->warmup($output);

        return Command::SUCCESS;
    }
}
