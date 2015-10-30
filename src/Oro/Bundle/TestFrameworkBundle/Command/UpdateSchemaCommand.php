<?php

namespace Oro\Bundle\TestFrameworkBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityExtendBundle\Tools\SchemaTrait;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigLoader;

class UpdateSchemaCommand extends ContainerAwareCommand
{
    use SchemaTrait;

    const TEST_ENTITY_NAMESPACE = 'Oro\Bundle\TestFrameworkBundle\Entity';
    const WORKFLOW_ENTITY_NAMESPACE = 'Oro\Bundle\WorkflowBundle\Entity';

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
                        'dump-sql',
                        null,
                        InputOption::VALUE_NONE,
                        'Dumps the generated SQL statements to the screen (does not execute them).'
                    ),
                ]
            );
    }

    /**
     * Runs command
     *
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->overrideRemoveNamespacedAssets();
        $this->overrideSchemaDiff();

        $output->writeln($this->getDescription());

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $metadata = array_filter(
            $em->getMetadataFactory()->getAllMetadata(),
            function ($doctrineMetadata) {
                /** @var ClassMetadataInfo $doctrineMetadata */
                $className = $doctrineMetadata->getReflectionClass()->getName();

                return strpos($className, self::TEST_ENTITY_NAMESPACE) === 0
                    || strpos($className, self::WORKFLOW_ENTITY_NAMESPACE) === 0;
            }
        );
        $schemaTool = new SchemaTool($em);

        $sqls = $schemaTool->getUpdateSchemaSql($metadata, true);
        if (0 === count($sqls)) {
            $output->writeln('Nothing to update - a database is already in sync with the current entity metadata.');
        } else {
            if ($input->getOption('dump-sql')) {
                $output->writeln(implode(';' . PHP_EOL, $sqls) . ';');
            }

            $output->writeln('Updating database schema...');
            $schemaTool->updateSchema($metadata, true);
            $output->writeln(
                sprintf(
                    'Database schema updated successfully! "<info>%s</info>" queries were executed',
                    count($sqls)
                )
            );
        }

        $this->loadEntityConfigData($em);
    }

    /**
     * @param EntityManager $em
     */
    protected function loadEntityConfigData(EntityManager $em)
    {
        $filter = function ($doctrineAllMetadata) {
            return array_filter(
                $doctrineAllMetadata,
                function ($item) {
                    /** @var ClassMetadataInfo $item */
                    $className = $item->getReflectionClass()->getName();

                    return strpos($className, self::TEST_ENTITY_NAMESPACE) === 0
                        || strpos($className, self::WORKFLOW_ENTITY_NAMESPACE) === 0;
                }
            );
        };

        /** @var ConfigLoader $loader */
        $loader = $this->getContainer()->get('oro_entity_config.config_loader');
        $loader->load(true, $filter);
    }
}
