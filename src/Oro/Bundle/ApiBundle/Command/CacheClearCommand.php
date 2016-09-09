<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\ApiBundle\Provider\ResourcesCache;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class CacheClearCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:api:cache:clear';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
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
        $io = new SymfonyStyle($input, $output);

        $io->comment('Clearing the cache for API resources');
        /** @var ResourcesCache $resourcesCache */
        $resourcesCache = $this->getContainer()->get('oro_api.resources_cache');
        $resourcesCache->clear();

        $io->comment('Clearing the cache for entity aliases');
        /** @var EntityAliasResolver $entityAliasResolver */
        $entityAliasResolver = $this->getContainer()->get('oro_api.entity_alias_resolver');
        $entityAliasResolver->clearCache();

        $io->success('API cache was successfully cleared.');
    }
}
