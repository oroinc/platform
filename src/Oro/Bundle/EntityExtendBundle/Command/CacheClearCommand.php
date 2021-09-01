<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears extended entity cache.
 */
class CacheClearCommand extends CacheCommand
{
    /** @var string */
    protected static $defaultName = 'oro:entity-extend:cache:clear';

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.')
            ->setDescription('Clears extended entity cache.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command clears extended entity cache.

  <info>php %command.full_name%</info>

The <info>--no-warmup</info> option can be used to skip warming up the cache after cleaning:

  <info>php %command.full_name% --no-warmup</info>

HELP
            )
            ->addUsage('--no-warmup')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Clear extended entity cache');

        $this->extendConfigDumper->clear();

        if (!$input->getOption('no-warmup')) {
            $this->warmup($output);
        }

        return 0;
    }
}
