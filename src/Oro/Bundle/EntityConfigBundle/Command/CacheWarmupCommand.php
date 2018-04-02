<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Oro\Bundle\EntityConfigBundle\Config\ConfigCacheWarmer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheWarmupCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-config:cache:warmup')
            ->setDescription('Warms up the entity config cache.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Warm up the entity config cache');

        /** @var ConfigCacheWarmer $configCacheWarmer */
        $configCacheWarmer = $this->getContainer()->get('oro_entity_config.config_cache_warmer');
        $configCacheWarmer->warmUpCache();
    }
}
