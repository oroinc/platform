<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class UpdateCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-config:update')
            ->setDescription('Update configuration data for entities.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite config\'s option values')
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Entity class name filter(regExp), for example: \'Oro\\\\Bundle\\\\User*\', \'^Oro\\\\(.*)\\\\Region$\''
            );
    }

    /**
     * {@inheritdoc}
     *
     * @TODO: add --dry-run option to show diff about what will be changed
     *
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getDescription());

        $configManager = $this->getConfigManager();
        /** @var ClassMetadataInfo[] $doctrineAllMetadata */
        $doctrineAllMetadata = $configManager->getEntityManager()->getMetadataFactory()->getAllMetadata();

        if ($filter = $input->getOption('filter')) {
            $doctrineAllMetadata = array_filter(
                $doctrineAllMetadata,
                function ($item) use ($filter) {
                    return preg_match('/'. str_replace('\\', '\\\\', $filter) . '/', $item->getName());
                }
            );
        }

        $force = $input->getOption('force');

        foreach ($doctrineAllMetadata as $doctrineMetadata) {
            $className = $doctrineMetadata->getName();
            if ($this->isConfigurableEntity($className, $configManager)) {
                if ($configManager->hasConfig($className)) {
                    $this->logMessage(
                        $output,
                        OutputInterface::VERBOSITY_NORMAL,
                        sprintf('Update config for "%s" entity.', $className)
                    );
                    $configManager->updateConfigEntityModel($className, $force);
                } else {
                    $this->logMessage(
                        $output,
                        OutputInterface::VERBOSITY_NORMAL,
                        sprintf('Create config for "%s" entity.', $className)
                    );
                    $configManager->createConfigEntityModel($className);
                }

                foreach ($doctrineMetadata->getFieldNames() as $fieldName) {
                    $fieldType = $doctrineMetadata->getTypeOfField($fieldName);
                    if ($configManager->hasConfig($className, $fieldName)) {
                        $this->logMessage(
                            $output,
                            OutputInterface::VERBOSITY_VERBOSE,
                            sprintf('  Update config for "%s" field.', $fieldName)
                        );
                        $configManager->updateConfigFieldModel($className, $fieldName, $force);
                    } else {
                        $this->logMessage(
                            $output,
                            OutputInterface::VERBOSITY_VERBOSE,
                            sprintf('  Create config for "%s" field.', $fieldName)
                        );
                        $configManager->createConfigFieldModel($className, $fieldName, $fieldType);
                    }
                }

                foreach ($doctrineMetadata->getAssociationNames() as $fieldName) {
                    $fieldType = $doctrineMetadata->isSingleValuedAssociation($fieldName) ? 'ref-one' : 'ref-many';
                    if ($configManager->hasConfig($className, $fieldName)) {
                        $this->logMessage(
                            $output,
                            OutputInterface::VERBOSITY_VERBOSE,
                            sprintf('  Update config for "%s" field.', $fieldName)
                        );
                        $configManager->updateConfigFieldModel($className, $fieldName, $force);
                    } else {
                        $this->logMessage(
                            $output,
                            OutputInterface::VERBOSITY_VERBOSE,
                            sprintf('  Create config for "%s" field.', $fieldName)
                        );
                        $configManager->createConfigFieldModel($className, $fieldName, $fieldType);
                    }
                }
            }
        }

        $configManager->clearConfigurableCache();

        $configManager->flush();

        $output->writeln('Completed');
    }

    /**
     * @param string        $className
     * @param ConfigManager $configManager
     * @return bool
     */
    protected function isConfigurableEntity($className, ConfigManager $configManager)
    {
        $classMetadata = $configManager->getEntityMetadata($className);
        if ($classMetadata) {
            // check if an entity is marked as configurable
            return $classMetadata->name === $className && $classMetadata->configurable;
        } else {
            // check if it is a custom entity
            return $configManager->hasConfig($className);
        }
    }

    /**
     * Writes a message to a console
     *
     * @param OutputInterface $output
     * @param int             $verbosity
     * @param string          $message
     */
    protected function logMessage(OutputInterface $output, $verbosity, $message)
    {
        if ($output->getVerbosity() >= $verbosity) {
            $output->writeln($message);
        }
    }
}
