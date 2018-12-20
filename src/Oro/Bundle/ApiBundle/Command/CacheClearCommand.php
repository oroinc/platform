<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The CLI command to clear Data API cache.
 */
class CacheClearCommand extends ContainerAwareCommand
{
    public const COMMAND_NAME = 'oro:api:cache:clear';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Clears Data API cache.')
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command clears Data API cache:

  <info>php %command.full_name%</info>

Usually you need to run this command when you add a new entity to <comment>Resources/config/oro/api.yml</comment>
or you add a new processor that changes a list of available through Data API resources
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

        /** @var CacheManager $cacheManager */
        $cacheManager = $this->getContainer()->get('oro_api.cache_manager');
        if ($noWarmup) {
            $io->comment('Clearing API cache...');
            $cacheManager->clearCaches();
        } else {
            $io->comment('Warming up API cache...');
            $cacheManager->warmUpCaches();
        }

        $io->success('API cache was successfully cleared.');
    }
}
