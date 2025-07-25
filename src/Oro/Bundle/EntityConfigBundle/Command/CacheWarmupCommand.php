<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Command;

use Oro\Bundle\EntityConfigBundle\Config\ConfigCacheWarmer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Warms up the entity config cache.
 */
#[AsCommand(
    name: 'oro:entity-config:cache:warmup',
    description: 'Warms up the entity config cache.'
)]
class CacheWarmupCommand extends Command
{
    private ConfigCacheWarmer $configCacheWarmer;

    public function __construct(ConfigCacheWarmer $configCacheWarmer)
    {
        $this->configCacheWarmer = $configCacheWarmer;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    public function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command warms up the entity config cache.

  <info>php %command.full_name%</info>

HELP
            )

        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Warm up the entity config cache');

        $this->configCacheWarmer->warmUpCache();

        return Command::SUCCESS;
    }
}
