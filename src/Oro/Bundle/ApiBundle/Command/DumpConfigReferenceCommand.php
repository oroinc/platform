<?php
declare(strict_types=1);

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
 * Dumps the reference structure for Resources/config/oro/api.yml.
 */
class DumpConfigReferenceCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:api:config:dump-reference';

    private ConfigExtensionRegistry $configExtensionRegistry;

    public function __construct(ConfigExtensionRegistry $configExtensionRegistry)
    {
        parent::__construct();

        $this->configExtensionRegistry = $configExtensionRegistry;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure(): void
    {
        $this
            ->addOption(
                'max-nesting-level',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum depth of nesting target entities.'
            )
            ->setDescription('Dumps the reference structure for Resources/config/oro/api.yml.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps the reference structure
for <comment>Resources/config/oro/api.yml</comment> files.

  <info>php %command.full_name%</info>

The <info>--max-nesting-level</info> option can be used to limit the depth of nesting target entities:

  <info>php %command.full_name% --max-nesting-level=<number></info>

HELP
            )
            ->addUsage('--max-nesting-level=<number>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, $output);

        $maxNestingLevel = $input->getOption('max-nesting-level');
        if (null === $maxNestingLevel) {
            $maxNestingLevel = $this->configExtensionRegistry->getMaxNestingLevel();
        } else {
            $maxNestingLevel = (int)$maxNestingLevel;
            if ($maxNestingLevel < 0 || $maxNestingLevel > $this->configExtensionRegistry->getMaxNestingLevel()) {
                throw new \LogicException(sprintf(
                    'The "max-nesting-level" should be a positive number less than or equal to %d.',
                    $this->configExtensionRegistry->getMaxNestingLevel()
                ));
            }
        }

        $configuration = new ApiConfiguration(
            $this->configExtensionRegistry,
            $maxNestingLevel
        );

        $output->writeln('# The structure of "Resources/config/oro/api.yml"');
        $dumper = new YamlReferenceDumper();
        $output->writeln($dumper->dump($configuration));

        return 0;
    }
}
