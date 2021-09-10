<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Command;

use Oro\Bundle\EntityConfigBundle\Config\ConfigCacheWarmer;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears the entity config cache.
 */
class CacheClearCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-config:cache:clear';

    private ConfigManager $configManager;
    private ConfigCacheWarmer $configCacheWarmer;

    public function __construct(ConfigManager $configManager, ConfigCacheWarmer $configCacheWarmer)
    {
        parent::__construct();

        $this->configManager = $configManager;
        $this->configCacheWarmer = $configCacheWarmer;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.')
            ->setDescription('Clears the entity config cache.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command clears the entity config cache.

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
        $output->writeln('Clear the entity config cache');

        $this->configManager->flushAllCaches();

        if (!$input->getOption('no-warmup')) {
            $this->configCacheWarmer->warmUpCache();
        }

        return 0;
    }
}
