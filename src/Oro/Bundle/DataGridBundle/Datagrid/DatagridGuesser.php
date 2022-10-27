<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

/**
 * The registry of datagrid column options guessers.
 */
class DatagridGuesser
{
    /** column formatting options key */
    const FORMATTER = 'formatter';

    /** column sorting options key */
    const SORTER = 'sorter';

    /** column filtering options key */
    const FILTER = 'filter';

    /** @var iterable|ColumnOptionsGuesserInterface[] */
    private $columnOptionsGuessers;

    /** @var ColumnOptionsGuesserInterface|null */
    private $columnOptionsGuesser;

    /**
     * @param iterable|ColumnOptionsGuesserInterface[] $columnOptionsGuessers
     */
    public function __construct(iterable $columnOptionsGuessers)
    {
        $this->columnOptionsGuessers = $columnOptionsGuessers;
    }

    /**
     * Merges all guesses with the given column options
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
     * Merges formatting guesses with the given column options
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
     * Merges sorting guesses with the given column options
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
     * Merges filtering guesses with the given column options
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

    private function getColumnOptionsGuesser(): ColumnOptionsGuesserInterface
    {
        if (null === $this->columnOptionsGuesser) {
            $guessers = [];
            foreach ($this->columnOptionsGuessers as $guesser) {
                $guessers[] = $guesser;
            }
            $this->columnOptionsGuesser = new ColumnOptionsGuesserChain($guessers);
        }

        return $this->columnOptionsGuesser;
    }
}
