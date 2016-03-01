<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigCacheWarmer;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class CacheClearCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-config:cache:clear')
            ->setDescription('Clears the entity config cache.')
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Clear the entity config cache');

        /** @var ConfigManager $configManager */
        $configManager = $this->getContainer()->get('oro_entity_config.config_manager');
        $configManager->flushAllCaches();

        if (!$input->getOption('no-warmup')) {
            /** @var ConfigCacheWarmer $configCacheWarmer */
            $configCacheWarmer = $this->getContainer()->get('oro_entity_config.config_cache_warmer');
            $configCacheWarmer->warmUpCache();
        }
    }
}
