<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The CLI command to show the structure of "Resources/config/oro/api.yml".
 */
class DumpConfigReferenceCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:config:dump-reference')
            ->setDescription('Dumps the structure of "Resources/config/oro/api.yml".')
            ->addOption(
                'max-nesting-level',
                null,
                InputOption::VALUE_REQUIRED,
                'The maximum number of nesting target entities.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);

        /** @var ConfigExtensionRegistry $configExtensionRegistry */
        $configExtensionRegistry = $this->getContainer()->get('oro_api.config_extension_registry');

        $maxNestingLevel = $input->getOption('max-nesting-level');
        if (null === $maxNestingLevel) {
            $maxNestingLevel = $configExtensionRegistry->getMaxNestingLevel();
        } else {
            $maxNestingLevel = (int)$maxNestingLevel;
            if ($maxNestingLevel < 0 || $maxNestingLevel > $configExtensionRegistry->getMaxNestingLevel()) {
                throw new \LogicException(
                    sprintf(
                        'The "max-nesting-level" should be a positive number less than or equal to %d.',
                        $configExtensionRegistry->getMaxNestingLevel()
                    )
                );
            }
        }

        $configuration = new ApiConfiguration(
            $configExtensionRegistry,
            $maxNestingLevel
        );

        $output->writeln('# The structure of "Resources/config/oro/api.yml"');
        $dumper = new YamlReferenceDumper();
        $output->writeln($dumper->dump($configuration));
    }
}
