<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityExtendBundle\Tools\ConfigFilter\ByInitialStateFilter;
use Oro\Bundle\EntityExtendBundle\Tools\ConfigFilter\ByOriginFilter;

class UpdateConfigCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-extend:update-config')
            ->setDescription('Prepare entity config')
            ->addOption(
                'update-custom',
                null,
                InputOption::VALUE_NONE,
                'Applies user changes that require schema update if specified'
            )
            ->addOption(
                'skip-origin',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Origin names which will be skipped during configuration update'
            )
            ->addOption(
                'initial-state-path',
                null,
                InputOption::VALUE_OPTIONAL,
                'A path to a file contains initial states of entity configs'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getDescription());

        $dumper = $this->getContainer()->get('oro_entity_extend.tools.dumper');
        $dumper->updateConfig($this->getFilter($input), $input->getOption('update-custom'));
    }

    /**
     * @param InputInterface $input
     *
     * @return callable|null
     */
    protected function getFilter(InputInterface $input)
    {
        $filter = null;

        $initialStatePath = $input->getOption('initial-state-path');
        if (!empty($initialStatePath)) {
            $initialStates = unserialize(file_get_contents($initialStatePath));
            if (!empty($initialStates)) {
                $filter = new ByInitialStateFilter($initialStates);
            }
        } else {
            $skippedOrigins = (array)$input->getOption('skip-origin');
            if (!empty($skippedOrigins)) {
                $filter = new ByOriginFilter($skippedOrigins);
            }
        }

        return $filter;
    }
}
