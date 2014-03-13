<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigLogger;
use Oro\Bundle\MigrationBundle\Command\Logger\OutputLogger;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigLoader;

class UpdateCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-config:update')
            ->setDescription('Updates configuration data for entities.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite config\'s option values')
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Entity class name filter(regExp)'
                . ', for example: \'Oro\\\\Bundle\\\\User*\', \'^Oro\\\\(.*)\\\\Region$\''
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Outputs modifications without apply them'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Update configuration data for entities');

        $filter = $input->getOption('filter');
        if ($filter) {
            $filter = function ($doctrineAllMetadata) use ($filter) {
                return array_filter(
                    $doctrineAllMetadata,
                    function ($item) use ($filter) {
                        /** @var ClassMetadataInfo $item */
                        return preg_match('/' . str_replace('\\', '\\\\', $filter) . '/', $item->getName());
                    }
                );
            };
        }

        $verbosity = $output->getVerbosity();
        if (!$input->getOption('dry-run')) {
            $verbosity--;
        }
        $logger = new ConfigLogger(new OutputLogger($output, true, $verbosity));
        /** @var ConfigLoader $loader */
        $loader = $this->getContainer()->get('oro_entity_config.config_loader');
        $loader->load(
            $input->getOption('force'),
            $filter,
            $logger,
            $input->getOption('dry-run')
        );
    }
}
