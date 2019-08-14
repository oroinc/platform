<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Oro\Bundle\EntityConfigBundle\Config\ConfigCacheWarmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Warms up the entity config cache.
 */
class CacheWarmupCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-config:cache:warmup';

    /** @var ConfigCacheWarmer */
    private $configCacheWarmer;

    /**
     * @param ConfigCacheWarmer $configCacheWarmer
     */
    public function __construct(ConfigCacheWarmer $configCacheWarmer)
    {
        $this->configCacheWarmer = $configCacheWarmer;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setDescription('Warms up the entity config cache.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Warm up the entity config cache');

        $this->configCacheWarmer->warmUpCache();
    }
}
