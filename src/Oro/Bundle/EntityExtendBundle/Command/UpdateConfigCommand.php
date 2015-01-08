<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateConfigCommand extends ContainerAwareCommand
{
    const NAME = 'oro:entity-extend:update-config';

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Prepare entity config')
            ->addOption(
                'skip-origin',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Origin names which will be skipped during configuration update'
            );
    }

    /**
     * Runs command
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $skippedOrigins = (array)$input->getOption('skip-origin');
        $output->writeln($this->getDescription());

        $dumper = $this->getContainer()->get('oro_entity_extend.tools.dumper');

        $dumper->updateConfig($skippedOrigins);
    }
}
