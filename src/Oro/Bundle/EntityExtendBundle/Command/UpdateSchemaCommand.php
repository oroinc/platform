<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScopeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\SchemaTrait;
use Oro\Bundle\EntityExtendBundle\Tools\SaveSchemaTool;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProvider;

class UpdateSchemaCommand extends ContainerAwareCommand
{
    use SchemaTrait;

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-extend:update-schema')
            ->setDescription('Synchronize extended and custom entities metadata with a database schema')
            ->setDefinition(
                [
                    new InputOption(
                        'dry-run',
                        null,
                        InputOption::VALUE_NONE,
                        'Dumps the generated SQL statements to the screen (does not execute them).'
                    ),
                    new InputOption(
                        'attributes-only',
                        null,
                        InputOption::VALUE_NONE,
                        'Executes update schema only for entities which has attributes.'
                    )
                ]
            );
    }

    /**
     * Runs command
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     *
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getDescription());

        $this->overrideRemoveNamespacedAssets();
        $this->overrideSchemaDiff();

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $metadata = $this->getClassesMetadata($input, $em);

        $schemaTool = new SaveSchemaTool($em);
        $sqls       = $schemaTool->getUpdateSchemaSql($metadata, true);
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

        if (!$input->getOption('dry-run')) {
            /** @var EnumSynchronizer $enumSynchronizer */
            $enumSynchronizer = $this->getContainer()->get('oro_entity_extend.enum_synchronizer');
            $enumSynchronizer->sync();
        }
    }

    /**
     * @param InputInterface $input
     * @param EntityManager $em
     *
     * @return array
     */
    protected function getClassesMetadata(InputInterface $input, EntityManager $em)
    {
        $extendEntityConfigProvider = $this->getContainer()
            ->get('oro_entity_config.provider.extend_entity_config_provider');

        if ($input->getOption('attributes-only')) {
            $extendEntityConfigProvider->enableAttributesOnly();
        }

        $extendConfigs = $extendEntityConfigProvider->getExtendEntityConfigs();
        $metadata = [];
        foreach ($extendConfigs as $extendConfig) {
            if (!ExtendScopeHelper::isAvailableForProcessing($extendConfig)) {
                continue;
            }

            $metadata[] = $em->getClassMetadata($extendConfig->getId()->getClassName());
        }

        return $metadata;
    }
}
