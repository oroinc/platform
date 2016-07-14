<?php

namespace Oro\Bundle\ApiBundle\Command;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\ApiBundle\Provider\ResourcesCache;

class CacheClearCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:cache:clear')
            ->setDescription('Clears Data API cache')
            ->setHelp(<<<EOF
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
        /** @var ResourcesCache $resourcesCache */
        $resourcesCache = $this->getContainer()->get('oro_api.resources_cache');
        $resourcesCache->clear();

        /** @var CacheProvider $aliasesCache */
        $aliasesCache = $this->getContainer()->get('oro_entity.entity_alias_cache');
        $aliasesCache->deleteAll();
    }
}
