<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            ->addArgument('entity', InputArgument::REQUIRED, 'The entity class name')
            ->addArgument('field', InputArgument::OPTIONAL, 'The field name')
            ->setDescription('Displays entity configuration.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = $input->getArgument('entity');
        $field  = $input->getArgument('field');

        if (empty($field)) {
            $this->writeEntityConfig($output, $entity);
        } else {
            $this->writeFieldConfig($output, $entity, $field);
        }
    }

    protected function writeEntityConfig(OutputInterface $output, $className)
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

    protected function writeFieldConfig(OutputInterface $output, $className, $fieldName)
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
