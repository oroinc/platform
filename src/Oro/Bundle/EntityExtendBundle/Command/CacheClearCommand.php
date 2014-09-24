<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class CacheClearCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('oro:entity-extend:cache:clear')
            ->setDescription('Clears the extended entity cache.')
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Clear the extended entity cache');

        /** @var ExtendConfigDumper $dumper */
        $dumper = $this->getContainer()->get('oro_entity_extend.tools.dumper');
        $dumper->clear();

        /** @var EntityProcessor $processor */
        $processor = $this->getContainer()->get('oro_entity_extend.extend.entity_processor');

        if (!$input->getOption('no-warmup')) {
            $dumper->dump();
            $processor->generateProxies();
        }
    }
}
