<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-config:debug')
            ->addArgument('entity', InputArgument::OPTIONAL, 'The entity class name')
            ->addArgument('field', InputArgument::OPTIONAL, 'The field name')
            ->addOption(
                'list',
                'l',
                InputOption::VALUE_NONE,
                'Show the list of configurable entities or fields'
            )
            ->addOption(
                'ref-non-configurable',
                null,
                InputOption::VALUE_NONE,
                'Show all fields that are references to non configurable entities'
            )
            ->setDescription('Displays entity configuration.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $entity         = $input->getArgument('entity');
        $field          = $input->getArgument('field');
        $isList         = $input->getOption('list');
        $isRefNonConfig = $input->getOption('ref-non-configurable');

        if ($isList) {
            if (empty($entity)) {
                $this->dumpEntityList($output);
            } else {
                $this->dumpFieldList($output, $entity);
            }
        } elseif ($isRefNonConfig) {
            $this->dumpNonConfigRef($output, $entity);
        } elseif (!empty($entity)) {
            if (empty($field)) {
                $this->dumpEntityConfig($output, $entity);
            } else {
                $this->dumpFieldConfig($output, $entity, $field);
            }
        }
    }

    protected function dumpEntityList(OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rows = $em->getConnection()->fetchAll(
            'SELECT class_name, mode FROM oro_entity_config ORDER BY class_name'
        );
        foreach ($rows as $row) {
            $output->writeln(sprintf('%s, Mode: %s', $row['class_name'], $row['mode']));
        }
    }

    protected function dumpFieldList(OutputInterface $output, $className)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rows = $em->getConnection()->fetchAll(
            'SELECT fc.field_name, fc.type, fc.mode FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = ?'
            . ' ORDER BY fc.field_name',
            [$className],
            ['string']
        );
        foreach ($rows as $row) {
            $output->writeln(sprintf('%s, Type: %s, Mode: %s', $row['field_name'], $row['type'], $row['mode']));
        }
    }

    protected function dumpNonConfigRef(OutputInterface $output, $className = null)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        if (empty($className)) {
            $rows = $em->getConnection()->fetchAll(
                'SELECT class_name FROM oro_entity_config ORDER BY class_name'
            );
            $classNames = [];
            foreach ($rows as $row) {
                $classNames[$row['class_name']] = $row['class_name'];
            }
        } else {
            $classNames = [$className => $className];
        }

        foreach ($classNames as $className) {
            $classMetadata = $em->getClassMetadata($className);
            $assocNames = $classMetadata->getAssociationNames();
            $isClassNameDumped = false;
            foreach ($assocNames as $assocName) {
                $targetClass = $classMetadata->getAssociationTargetClass($assocName);
                if (!isset($classNames[$targetClass])) {
                    if (!$isClassNameDumped) {
                        $isClassNameDumped = true;
                        $output->writeln(sprintf('%s:', $className));
                    }
                    $fieldInfo = $em->getConnection()->fetchAll(
                        'SELECT fc.type FROM oro_entity_config ec'
                        . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
                        . ' WHERE ec.class_name = ? AND fc.field_name = ?',
                        [$className, $assocName],
                        ['string', 'string']
                    );
                    $output->writeln(
                        sprintf(
                            '  %s, %s, ref to %s',
                            $assocName,
                            $fieldInfo[0]['type'],
                            $targetClass
                        )
                    );
                }
            }
        }
    }

    protected function dumpEntityConfig(OutputInterface $output, $className)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rows = $em->getConnection()->fetchAll(
            'SELECT * FROM oro_entity_config WHERE class_name = ?',
            [$className],
            ['string']
        );
        foreach ($rows as $row) {
            $output->writeln(sprintf('Class: %s', $row['class_name']));
            $output->writeln(sprintf('Mode:  %s', $row['mode']));
            $output->writeln('Values:');
            $output->writeln(print_r(unserialize($row['data']), true));
        }
    }

    protected function dumpFieldConfig(OutputInterface $output, $className, $fieldName)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rows = $em->getConnection()->fetchAll(
            'SELECT ec.class_name, fc.* FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = ? AND fc.field_name = ?',
            [$className, $fieldName],
            ['string', 'string']
        );
        foreach ($rows as $row) {
            $output->writeln(sprintf('Class: %s', $row['class_name']));
            $output->writeln(sprintf('Field: %s', $row['field_name']));
            $output->writeln(sprintf('Type:  %s', $row['type']));
            $output->writeln(sprintf('Mode:  %s', $row['mode']));
            $output->writeln('Values:');
            $output->writeln(print_r(unserialize($row['data']), true));
        }
    }
}
