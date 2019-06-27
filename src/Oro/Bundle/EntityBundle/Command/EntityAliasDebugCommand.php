<?php

namespace Oro\Bundle\EntityBundle\Command;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Displays entity aliases.
 */
class EntityAliasDebugCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-alias:debug';

    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    /**
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setDescription('Displays entity aliases.');
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
        $entityAliases = $this->entityAliasResolver->getAll();

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
