<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class CacheWarmupCommand extends ContainerAwareCommand
{
    const NAME = 'oro:entity-extend:cache:warmup';

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Warms up the extended entity cache.');
    }

    /**
     * Runs command
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Dump the configuration of extended entities to the cache');

        /** @var ExtendConfigDumper $dumper */
        $dumper = $this->getContainer()->get('oro_entity_extend.tools.dumper');
        $dumper->dump();
    }
}
