<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigLogger;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsParser;
use Oro\Component\Log\OutputLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates extended entities configuration during a database structure migration process.
 */
class MigrationUpdateConfigCommand extends ContainerAwareCommand
{
    /** @var string */
    protected static $defaultName = 'oro:entity-extend:migration:update-config';

    /** var ExtendOptionsParser **/
    private $extendOptionsParser;

    /** var ExtendConfigProcessor **/
    private $extendConfigProcessor;

    /**
     * @param ExtendOptionsParser $extendOptionsParser
     * @param ExtendConfigProcessor $extendConfigProcessor
     */
    public function __construct(ExtendOptionsParser $extendOptionsParser, ExtendConfigProcessor $extendConfigProcessor)
    {
        $this->extendOptionsParser = $extendOptionsParser;
        $this->extendConfigProcessor = $extendConfigProcessor;
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

        /** @var string $optionsPath */
        $optionsPath = $this->getContainer()->getParameter('oro_entity_extend.migration.config_processor.options.path');
        if (is_file($optionsPath)) {
            $options = unserialize(file_get_contents($optionsPath));

            $dryRun = $input->getOption('dry-run');

            /** @var ExtendOptionsParser $parser */
            $parser  = $this->getContainer()->get('oro_entity_extend.migration.options_parser');
            $parser->setDryRunMode($dryRun);

            $options = $this->extendOptionsParser->parseOptions($options);

            $logger = new ConfigLogger(new OutputLogger($output));

            $this->extendConfigProcessor->processConfigs(
                $options,
                $logger,
                $dryRun
            );
        } else {
            $output->writeln(
                sprintf('<error>The options file "%s" was not found.</error>', $optionsPath)
            );
        }
    }
}
