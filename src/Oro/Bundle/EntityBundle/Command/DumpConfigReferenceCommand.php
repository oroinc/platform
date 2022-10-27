<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Command;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dumps the reference structure for Resources/config/oro/entity.yml.
 */
class DumpConfigReferenceCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity:config:dump-reference';

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Dumps the reference structure for Resources/config/oro/entity.yml.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps the reference structure
for <comment>Resources/config/oro/entity.yml</comment> files.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);

        $output->writeln('# The structure of "Resources/config/oro/entity.yml"');
        $dumper = new YamlReferenceDumper();
        $output->writeln($dumper->dump(new EntityConfiguration()));

        return 0;
    }
}
