<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigLoader;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigLogger;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates configuration data for entities.
 */
class UpdateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-config:update';

    /** @var ConfigLoader */
    private $configLoader;

    /**
     * @param ConfigLoader $configLoader
     */
    public function __construct(ConfigLoader $configLoader)
    {
        parent::__construct();

        $this->configLoader = $configLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
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
        $this->configLoader->load(
            $input->getOption('force'),
            $filter,
            $logger,
            $input->getOption('dry-run')
        );
    }
}
