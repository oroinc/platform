<?php

namespace Oro\Bundle\QueryDesignerBundle\Command;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Configuration;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The CLI command to show the structure of "Resources/config/oro/query_designer.yml".
 */
class DumpConfigReferenceCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:query-designer:config:dump-reference';

    /** @var Configuration */
    private $configuration;

    /**
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        parent::__construct();
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Dumps the structure of "Resources/config/oro/query_designer.yml".');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);

        $output->writeln('# The structure of "Resources/config/oro/query_designer.yml"');
        $dumper = new YamlReferenceDumper();
        $output->writeln($dumper->dump($this->configuration));
    }
}
