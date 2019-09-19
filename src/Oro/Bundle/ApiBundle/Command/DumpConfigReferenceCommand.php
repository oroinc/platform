<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The CLI command to show the structure of "Resources/config/oro/api.yml".
 */
class DumpConfigReferenceCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:api:config:dump-reference';

    /** @var ConfigExtensionRegistry */
    private $configExtensionRegistry;

    /**
     * @param ConfigExtensionRegistry $configExtensionRegistry
     */
    public function __construct(ConfigExtensionRegistry $configExtensionRegistry)
    {
        parent::__construct();

        $this->configExtensionRegistry = $configExtensionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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

        $maxNestingLevel = $input->getOption('max-nesting-level');
        if (null === $maxNestingLevel) {
            $maxNestingLevel = $this->configExtensionRegistry->getMaxNestingLevel();
        } else {
            $maxNestingLevel = (int)$maxNestingLevel;
            if ($maxNestingLevel < 0 || $maxNestingLevel > $this->configExtensionRegistry->getMaxNestingLevel()) {
                throw new \LogicException(
                    sprintf(
                        'The "max-nesting-level" should be a positive number less than or equal to %d.',
                        $this->configExtensionRegistry->getMaxNestingLevel()
                    )
                );
            }
        }

        $configuration = new ApiConfiguration(
            $this->configExtensionRegistry,
            $maxNestingLevel
        );

        $output->writeln('# The structure of "Resources/config/oro/api.yml"');
        $dumper = new YamlReferenceDumper();
        $output->writeln($dumper->dump($configuration));
    }
}
