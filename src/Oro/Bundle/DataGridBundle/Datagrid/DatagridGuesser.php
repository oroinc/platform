<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class DatagridGuesser
{
    /** column formatting options key */
    const FORMATTER = 'formatter';

    /** column sorting options key */
    const SORTER = 'sorter';

    /** column filtering options key */
    const FILTER = 'filter';

    /** @var ContainerInterface */
    protected $container;

    /** @var string[] */
    protected $columnOptionsGuesserServiceIds;

    /** @var ColumnOptionsGuesserInterface */
    protected $columnOptionsGuesser;

    /**
     * @param ContainerInterface $container
     * @param string[]           $columnOptionsGuesserServiceIds
     */
    public function __construct(ContainerInterface $container, array $columnOptionsGuesserServiceIds)
    {
        $this->container                      = $container;
        $this->columnOptionsGuesserServiceIds = $columnOptionsGuesserServiceIds;
    }

    /**
     * Applies all guesses for a column
     *
     * @param string $class         The fully qualified class name
     * @param string $property      The name of the property to guess for
     * @param string $type          The property type
     * @param array  $columnOptions The column options
     */
    public function applyColumnGuesses($class, $property, $type, array &$columnOptions)
    {
        $this->applyColumnFormatterGuesses($class, $property, $type, $columnOptions);
        $this->applyColumnSorterGuesses($class, $property, $type, $columnOptions);
        $this->applyColumnFilterGuesses($class, $property, $type, $columnOptions);
    }

    /**
     * Applies formatting guesses for a column
     *
     * @param string $class         The fully qualified class name
     * @param string $property      The name of the property to guess for
     * @param string $type          The property type
     * @param array  $columnOptions The column options
     */
    public function applyColumnFormatterGuesses($class, $property, $type, array &$columnOptions)
    {
        $columnGuess = $this->getColumnOptionsGuesser()->guessFormatter($class, $property, $type);
        if ($columnGuess) {
            if (isset($columnOptions[self::FORMATTER])) {
                $columnOptions[self::FORMATTER] = array_merge(
                    $columnGuess->getOptions(),
                    $columnOptions[self::FORMATTER]
                );
            } else {
                $columnOptions[self::FORMATTER] = $columnGuess->getOptions();
            }
        }
    }

    /**
     * Applies sorting guesses for a column
     *
     * @param string $class         The fully qualified class name
     * @param string $property      The name of the property to guess for
     * @param string $type          The property type
     * @param array  $columnOptions The column options
     */
    public function applyColumnSorterGuesses($class, $property, $type, array &$columnOptions)
    {
        $columnGuess = $this->getColumnOptionsGuesser()->guessSorter($class, $property, $type);
        if ($columnGuess) {
            if (isset($columnOptions[self::SORTER])) {
                $columnOptions[self::SORTER] = array_merge($columnGuess->getOptions(), $columnOptions[self::SORTER]);
            } else {
                $columnOptions[self::SORTER] = $columnGuess->getOptions();
            }
        }
    }

    /**
     * Applies filtering guesses for a column
     *
     * @param string $class         The fully qualified class name
     * @param string $property      The name of the property to guess for
     * @param string $type          The property type
     * @param array  $columnOptions The column options
     */
    public function applyColumnFilterGuesses($class, $property, $type, array &$columnOptions)
    {
        $columnGuess = $this->getColumnOptionsGuesser()->guessFilter($class, $property, $type);
        if ($columnGuess) {
            if (isset($columnOptions[self::FILTER])) {
                $columnOptions[self::FILTER] = array_merge($columnGuess->getOptions(), $columnOptions[self::FILTER]);
            } else {
                $columnOptions[self::FILTER] = $columnGuess->getOptions();
            }
        }
    }

    /**
     * Saves column options to a grid configuration
     *
     * @param DatagridConfiguration $datagridConfig The grid configuration
     * @param string                $columnName     The name of the column
     * @param array                 $columnOptions  The column options
     */
    public function setColumnOptions(DatagridConfiguration $datagridConfig, $columnName, array &$columnOptions)
    {
        if (isset($columnOptions[DatagridGuesser::FORMATTER])) {
            $datagridConfig->offsetSetByPath(
                sprintf('[columns][%s]', $columnName),
                $columnOptions[DatagridGuesser::FORMATTER]
            );
        }
        if (isset($columnOptions[DatagridGuesser::SORTER])) {
            $datagridConfig->offsetSetByPath(
                sprintf('[sorters][columns][%s]', $columnName),
                $columnOptions[DatagridGuesser::SORTER]
            );
        }
        if (isset($columnOptions[DatagridGuesser::FILTER])) {
            $datagridConfig->offsetSetByPath(
                sprintf('[filters][columns][%s]', $columnName),
                $columnOptions[DatagridGuesser::FILTER]
            );
        }
    }

    /**
     * @return ColumnOptionsGuesserInterface
     */
    protected function getColumnOptionsGuesser()
    {
        if ($this->columnOptionsGuesser === null) {
            $guessers = array();
            foreach ($this->columnOptionsGuesserServiceIds as $serviceId) {
                $guessers[] = $this->container->get($serviceId);
            }
            $this->columnOptionsGuesser = new ColumnOptionsGuesserChain($guessers);
        }

        return $this->columnOptionsGuesser;
    }
}
