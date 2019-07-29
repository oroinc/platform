<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigLogger;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsParser;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates extended entities configuration during a database structure migration process.
 */
class MigrationUpdateConfigCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-extend:migration:update-config';

    /** var ExtendOptionsParser **/
    private $extendOptionsParser;

    /** var ExtendConfigProcessor **/
    private $extendConfigProcessor;

    /** @var string */
    private $optionsPath;

    /**
     * @param ExtendOptionsParser $extendOptionsParser
     * @param ExtendConfigProcessor $extendConfigProcessor
     * @param string $optionsPath
     */
    public function __construct(
        ExtendOptionsParser $extendOptionsParser,
        ExtendConfigProcessor $extendConfigProcessor,
        string $optionsPath
    ) {
        $this->extendOptionsParser = $extendOptionsParser;
        $this->extendConfigProcessor = $extendConfigProcessor;
        $this->optionsPath = $optionsPath;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setDescription(
                'Updates extended entities configuration during a database structure migration process.'
                . ' This is an internal command. Please do not run it manually.'
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
        $output->writeln('Update extended entities configuration');

        if (is_file($this->optionsPath)) {
            $options = unserialize(file_get_contents($this->optionsPath));

            $dryRun = $input->getOption('dry-run');
            $this->extendOptionsParser->setDryRunMode($dryRun);

            $options = $this->extendOptionsParser->parseOptions($options);

            $logger = new ConfigLogger(new OutputLogger($output));

            $this->extendConfigProcessor->processConfigs(
                $options,
                $logger,
                $dryRun
            );
        } else {
            $output->writeln(
                sprintf('<error>The options file "%s" was not found.</error>', $this->optionsPath)
            );
        }
    }
}
