<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

interface ColumnOptionsGuesserInterface
{
    /**
     * Returns a column formatting guess for a property name of a class
     *
     * @param string $class    The fully qualified class name
     * @param string $property The name of the property to guess for
     * @param string $type     The property type
     *
     * @return Guess\ColumnGuess|null A guess for the column's options
     */
    public function guessFormatter($class, $property, $type);

    /**
     * Returns a column sorting guess for a property name of a class
     *
     * @param string $class    The fully qualified class name
     * @param string $property The name of the property to guess for
     * @param string $type     The property type
     *
     * @return Guess\ColumnGuess|null A guess for the column's options
     */
    public function guessSorter($class, $property, $type);

    /**
     * Returns a column filtering guess for a property name of a class
     *
     * @param string $class    The fully qualified class name
     * @param string $property The name of the property to guess for
     * @param string $type     The property type
     *
     * @return Guess\ColumnGuess|null A guess for the column's options
     */
    public function guessFilter($class, $property, $type);
}
