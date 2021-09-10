<?php
declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Command;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dumps the reference structure for Resources/views/layouts/THEME_NAME/theme.yml.
 */
class DumpConfigReferenceCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:layout:config:dump-reference';

    private ThemeConfiguration $themeConfiguration;

    public function __construct(ThemeConfiguration $themeConfiguration)
    {
        parent::__construct();
        $this->themeConfiguration = $themeConfiguration;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Dumps the reference structure for Resources/views/layouts/THEME_NAME/theme.yml.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps the reference structure
for <comment>Resources/views/layouts/*/theme.yml</comment> files.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);

        $output->writeln('# The structure of "Resources/views/layouts/*/theme.yml"');
        $dumper = new YamlReferenceDumper();
        $output->writeln($dumper->dump($this->themeConfiguration));

        $output->writeln('# Also the following files can be used to define additional config sections:');
        foreach ($this->themeConfiguration->getAdditionalConfigFileNames() as $fileName) {
            $output->writeln(sprintf(' - Resources/views/layouts/*/config/%s', $fileName));
        }

        return 0;
    }
}
