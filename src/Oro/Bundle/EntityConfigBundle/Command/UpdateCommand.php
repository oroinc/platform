<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        foreach ($doctrineAllMetadata as $doctrineMetadata) {
            $className = $doctrineMetadata->getName();
            $classMetadata = $configManager->getEntityMetadata($className);
            if ($classMetadata
                && $classMetadata->name === $className
                && $classMetadata->configurable
            ) {
                $output->writeln('Update entity "' . $className . '"');

                if ($configManager->hasConfig($classMetadata->name)) {
                    $configManager->updateConfigEntityModel($className, $input->getOption('force'));
                } else {
                    $configManager->createConfigEntityModel($className);
                }

                foreach ($doctrineMetadata->getFieldNames() as $fieldName) {
                    $fieldType = $doctrineMetadata->getTypeOfField($fieldName);
                    if ($configManager->hasConfig($className, $fieldName)) {
                        $configManager->updateConfigFieldModel($className, $fieldName, $input->getOption('force'));
                    } else {
                        $configManager->createConfigFieldModel($className, $fieldName, $fieldType);
                    }
                }

                foreach ($doctrineMetadata->getAssociationNames() as $fieldName) {
                    $fieldType = $doctrineMetadata->isSingleValuedAssociation($fieldName) ? 'ref-one' : 'ref-many';
                    if ($configManager->hasConfig($className, $fieldName)) {
                        $configManager->updateConfigFieldModel($className, $fieldName, $input->getOption('force'));
                    } else {
                        $configManager->createConfigFieldModel($className, $fieldName, $fieldType);
                    }
                }
            }
        }

        $configManager->clearConfigurableCache();

        $configManager->flush();

        $output->writeln('Completed');
    }
}
