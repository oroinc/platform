<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Oro\Bundle\EntityConfigBundle\Command\Logger\OutputLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;

class UpdateCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-config:update')
            ->setDescription('Update configuration data for entities.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite config\'s option values')
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Entity class name filter(regExp), for example: \'Oro\\\\Bundle\\\\User*\', \'^Oro\\\\(.*)\\\\Region$\''
            );
    }

    /**
     * {@inheritdoc}
     *
     * @TODO: add --dry-run option to show diff about what will be changed
     *
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getDescription());

        /** @var ConfigDumper $configDumper */
        $configDumper = $this->getContainer()->get('oro_entity_config.tools.dumper');

        $configDumper->setLogger(new OutputLogger($output));

        $filter = $input->getOption('filter');
        if ($filter) {
            $filter = function ($doctrineAllMetadata) use ($filter) {
                return array_filter(
                    $doctrineAllMetadata,
                    function ($item) use ($filter) {
                        return preg_match('/'. str_replace('\\', '\\\\', $filter) . '/', $item->getName());
                    }
                );
            };
        }

        $configDumper->updateConfigs($input->getOption('force'), $filter);

        $output->writeln('Completed');
    }
}
