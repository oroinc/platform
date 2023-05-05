<?php
declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clears the API cache.
 */
class CacheClearCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:api:cache:clear';

    private CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        parent::__construct();

        $this->cacheManager = $cacheManager;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure(): void
    {
        $this
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache')
            ->setDescription('Clears the API cache.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command clears the API cache. It is usually required
after adding a new entity to <comment>Resources/config/oro/api.yml</comment> or a new processor
that changes a list of available API resources.

  <info>php %command.full_name%</info>

The <info>--no-warmup</info> option can be used to skip warming up the cache after cleaning:

  <info>php %command.full_name% --no-warmup</info>

HELP
            )
            ->addUsage('--no-warmup')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $noWarmup = $input->getOption('no-warmup');

        if ($noWarmup) {
            $io->comment('Clearing API cache...');
            $this->cacheManager->clearCaches();
        } else {
            $io->comment('Warming up API cache...');
            $this->cacheManager->warmUpCaches();
        }

        $io->success('API cache was successfully cleared.');

        return 0;
    }
}
