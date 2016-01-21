<?php

namespace Oro\Bundle\SecurityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Entity\PermissionDefinition;

class LoadPermissionConfigurationCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository
     */
    protected $definitionRepository;

    /**
     * @var PermissionConfigurationBuilder
     */
    protected $configurationBuilder;

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        }

        return $this->entityManager;
    }

    /**
     * @return EntityRepository
     */
    protected function getDefinitionRepository()
    {
        if (!$this->definitionRepository) {
            $this->definitionRepository
                = $this->getEntityManager()->getRepository('OroSecurityBundle:PermissionDefinition');
        }

        return $this->definitionRepository;
    }

    /**
     * @return PermissionConfigurationBuilder
     */
    protected function getConfigurationBuilder()
    {
        if (!$this->configurationBuilder) {
            $this->configurationBuilder
                = $this->getContainer()->get('oro_security.configuration.builder.permission_configuration');
        }

        return $this->configurationBuilder;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:permission:configuration:load')
            ->setDescription('Load permission configuration from configuration files to the database')
            ->addOption(
                'directories',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Directories used to find configuration files'
            )
            ->addOption(
                'definitions',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Names of the permission definitions that should be loaded'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $usedDirectories = $input->getOption('directories');
        $usedDirectories = $usedDirectories ?: null;

        $usedDefinitions = $input->getOption('definitions');
        $usedDefinitions = $usedDefinitions ?: null;

        /** @var PermissionConfigurationProvider $configurationProvider */
        $configurationProvider = $this->getContainer()->get('oro_security.configuration.provider.permission_config');
        $permissionConfiguration = $configurationProvider->getPermissionConfiguration(
            $usedDirectories,
            $usedDefinitions
        );

        // permission definitions
        $definitionsConfiguration = $permissionConfiguration[PermissionConfigurationProvider::ROOT_NODE_NAME];
        $this->loadDefinitions($output, $definitionsConfiguration);
    }

    /**
     * @param OutputInterface $output
     * @param array $configuration
     */
    protected function loadDefinitions(OutputInterface $output, array $configuration)
    {
        $definitions = $this->getConfigurationBuilder()->buildPermissionDefinitions($configuration);

        if ($definitions) {
            $output->writeln('Loading permission definitions...');

            $entityManager = $this->getEntityManager();
            $definitionRepository = $this->getDefinitionRepository();

            foreach ($definitions as $definition) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $definition->getName()));

                /** @var PermissionDefinition $existingDefinition */
                $existingDefinition = $definitionRepository->findOneBy(['name' => $definition->getName()]);

                // definition should be overridden if definition with such name already exists
                if ($existingDefinition) {
                    $existingDefinition->import($definition);
                } else {
                    $entityManager->persist($definition);
                }
            }

            $entityManager->flush();
        } else {
            $output->writeln('No permission definitions found.');
        }
    }
}
