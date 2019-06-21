<?php

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

    /** @var ConfigManager */
    private $configManager;

    /** @var ConfigCacheWarmer */
    private $configCacheWarmer;

    /**
     * @param ConfigManager $configManager
     * @param ConfigCacheWarmer $configCacheWarmer
     */
    public function __construct(ConfigManager $configManager, ConfigCacheWarmer $configCacheWarmer)
    {
        parent::__construct();

        $this->configManager = $configManager;
        $this->configCacheWarmer = $configCacheWarmer;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setDescription('Clears the entity config cache.')
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Clear the entity config cache');

        $this->configManager->flushAllCaches();

        if (!$input->getOption('no-warmup')) {
            $this->configCacheWarmer->warmUpCache();
        }
    }
}
