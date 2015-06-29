<?php

namespace Oro\Bundle\EntityBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class EntityAliasDebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-alias:debug')
            ->setDescription('Displays entity aliases.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->outputEntityAliases($output);
    }

    /**
     * @param OutputInterface $output
     */
    protected function outputEntityAliases(OutputInterface $output)
    {
        /** @var EntityAliasResolver $entityAliasResolver */
        $entityAliasResolver = $this->getContainer()->get('oro_entity.entity_alias_resolver');

        $entityAliases = $entityAliasResolver->getAll();

        // sort alphabetically by the entity class and move BAP entities at the top
        ksort($entityAliases);
        $sortedEntityClasses = array_merge(
            array_filter(
                array_keys($entityAliases),
                function ($class) {
                    return strpos(strtolower($class), 'oro\\') === 0;
                }
            ),
            array_filter(
                array_keys($entityAliases),
                function ($class) {
                    return strpos(strtolower($class), 'oro\\') !== 0;
                }
            )
        );

        $maxClass       = strlen('class');
        $maxAlias       = strlen('alias');
        $maxPluralAlias = strlen('plural alias');
        foreach ($entityAliases as $class => $entityAlias) {
            $maxClass       = max($maxClass, strlen($class));
            $maxAlias       = max($maxAlias, strlen($entityAlias->getAlias()));
            $maxPluralAlias = max($maxPluralAlias, strlen($entityAlias->getPluralAlias()));
        }

        $format       = '%-' . $maxClass . 's %-' . $maxAlias . 's %-' . $maxPluralAlias . 's';
        $formatHeader = '%-' . ($maxClass + 19) . 's %-' . ($maxAlias + 19) . 's %-' . ($maxPluralAlias + 19) . 's';
        $output->writeln(
            sprintf(
                $formatHeader,
                '<comment>Class</comment>',
                '<comment>Alias</comment>',
                '<comment>Plural Alias</comment>'
            )
        );
        foreach ($sortedEntityClasses as $class) {
            $output->writeln(
                sprintf(
                    $format,
                    $class,
                    $entityAliases[$class]->getAlias(),
                    $entityAliases[$class]->getPluralAlias()
                ),
                OutputInterface::OUTPUT_RAW
            );
        }
    }
}
