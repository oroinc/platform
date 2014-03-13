<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigLogger;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\MigrationBundle\Command\Logger\OutputLogger;

class MigrationUpdateConfigCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-extend:migration:update-config')
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

            $options = Yaml::parse(file_get_contents($optionsPath));

            $logger = new ConfigLogger(new OutputLogger($output));
            /** @var ExtendConfigProcessor $processor */
            $processor = $this->getContainer()->get('oro_entity_extend.migration.config_processor');
            $processor->processConfigs(
                $options,
                $logger,
                $input->getOption('dry-run')
            );
        } else {
            $output->writeln(
                sprintf('<error>The options file "%s" was not found.</error>', $optionsPath)
            );
        }
    }
}
