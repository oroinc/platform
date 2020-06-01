<?php

namespace Oro\Bundle\EntityBundle\Command;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The CLI command to show the structure of "Resources/config/oro/entity.yml".
 */
class DumpConfigReferenceCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity:config:dump-reference';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Dumps the structure of "Resources/config/oro/entity.yml".');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);

        $output->writeln('# The structure of "Resources/config/oro/entity.yml"');
        $dumper = new YamlReferenceDumper();
        $output->writeln($dumper->dump(new EntityConfiguration()));
    }
}
