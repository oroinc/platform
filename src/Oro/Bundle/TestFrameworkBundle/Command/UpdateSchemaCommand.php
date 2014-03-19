<?php

namespace Oro\Bundle\TestFrameworkBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSchemaCommand extends ContainerAwareCommand
{
    const TEST_ENTITY_NAMESPACE = 'Oro\Bundle\TestFrameworkBundle\Entity';

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this
            ->setName('oro:test:schema:update')
            ->setDescription('Synchronize test entities metadata with a database schema')
            ->setDefinition(
                [
                    new InputOption(
                        'dry-run',
                        null,
                        InputOption::VALUE_NONE,
                        'Dumps the generated SQL statements to the screen (does not execute them).'
                    )
                ]
            );
    }

    /**
     * Runs command
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getDescription());

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $metadata   = array_filter(
            $em->getMetadataFactory()->getAllMetadata(),
            function ($doctrineMetadata) {
                /** @var ClassMetadataInfo $doctrineMetadata */
                return strpos($doctrineMetadata->getReflectionClass()->getName(), self::TEST_ENTITY_NAMESPACE) === 0;
            }
        );
        $schemaTool = new SchemaTool($em);

        $sqls = $schemaTool->getUpdateSchemaSql($metadata, true);
        if (0 === count($sqls)) {
            $output->writeln('Nothing to update - a database is already in sync with the current entity metadata.');
        } else {
            if ($input->getOption('dry-run')) {
                $output->writeln(implode(';' . PHP_EOL, $sqls) . ';');
            } else {
                $output->writeln('Updating database schema...');
                $schemaTool->updateSchema($metadata, true);
                $output->writeln(
                    sprintf(
                        'Database schema updated successfully! "<info>%s</info>" queries were executed',
                        count($sqls)
                    )
                );
            }
        }
    }
}
