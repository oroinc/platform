<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Command;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dumps the reference structure for Resources/config/oro/entity.yml.
 */
#[AsCommand(
    name: 'oro:entity:config:dump-reference',
    description: 'Dumps the reference structure for Resources/config/oro/entity.yml.'
)]
class DumpConfigReferenceCommand extends Command
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
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
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, $output);

        $output->writeln('# The structure of "Resources/config/oro/entity.yml"');
        $dumper = new YamlReferenceDumper();
        $output->writeln($dumper->dump(new EntityConfiguration()));

        return Command::SUCCESS;
    }
}
