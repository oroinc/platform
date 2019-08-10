<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The CLI command to clear API cache.
 */
class CacheClearCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:api:cache:clear';

    /** @var CacheManager */
    private $cacheManager;

    /**
     * @param CacheManager $cacheManager
     */
    public function __construct(CacheManager $cacheManager)
    {
        parent::__construct();

        $this->cacheManager = $cacheManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Clears API cache.')
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command clears API cache:

  <info>php %command.full_name%</info>

Usually you need to run this command when you add a new entity to <comment>Resources/config/oro/api.yml</comment>
or you add a new processor that changes a list of available through API resources
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
    }
}
