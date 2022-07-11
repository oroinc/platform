<?php

namespace Oro\Bundle\FeatureToggleBundle\Command;

use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dumps the reference structure for "Resources/config/oro/features.yml".
 */
class ConfigDumpReferenceCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:feature-toggle:config:dump-reference';

    private FeatureToggleConfiguration $configuration;

    public function __construct(FeatureToggleConfiguration $configuration)
    {
        parent::__construct();
        $this->configuration = $configuration;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Dumps the reference structure for Resources/config/oro/features.yml.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps the reference structure
for <comment>Resources/config/oro/features.yml</comment> files.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, $output);

        $output->writeln('# The structure of "Resources/config/oro/features.yml"');
        $dumper = new YamlReferenceDumper();
        $output->writeln($dumper->dump($this->configuration));

        return 0;
    }
}
