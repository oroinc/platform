<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Oro\Bundle\EntityExtendBundle\Tools\ConfigFilter\ByInitialStateFilter;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The CLI command to update extend entity config
 */
class UpdateConfigCommand extends Command
{
    protected static $defaultName = 'oro:entity-extend:update-config';

    /** @var ExtendConfigDumper */
    private $extendConfigDumper;

    /**
     * @param ExtendConfigDumper $extendConfigDumper
     */
    public function __construct(ExtendConfigDumper $extendConfigDumper)
    {
        $this->extendConfigDumper = $extendConfigDumper;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setDescription('Prepare entity config')
            ->addOption(
                'update-custom',
                null,
                InputOption::VALUE_NONE,
                'Applies user changes that require schema update if specified'
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

        $this->extendConfigDumper->updateConfig($this->getFilter($input), $input->getOption('update-custom'));
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
        }

        return $filter;
    }
}
