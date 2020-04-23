<?php

namespace Oro\Bundle\LayoutBundle\Command;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The CLI command to show the structure of "Resources/views/layouts/{folder}/theme.yml".
 */
class DumpConfigReferenceCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:layout:config:dump-reference';

    /** @var ThemeConfiguration */
    private $themeConfiguration;

    /**
     * @param ThemeConfiguration $themeConfiguration
     */
    public function __construct(ThemeConfiguration $themeConfiguration)
    {
        parent::__construct();
        $this->themeConfiguration = $themeConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Dumps the structure of "Resources/views/layouts/*/theme.yml".');
    }

    /**
     * {@inheritdoc}
     */
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
    }
}
