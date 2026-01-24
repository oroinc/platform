<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

/**
 * Provides a base implementation for column options guessers.
 *
 * This abstract class implements the {@see ColumnOptionsGuesserInterface} with default null-returning
 * methods, allowing concrete implementations to override only the specific guessing methods
 * they need to customize.
 */
class AbstractColumnOptionsGuesser implements ColumnOptionsGuesserInterface
{
    #[\Override]
    public function guessFormatter($class, $property, $type)
    {
        return null;
    }

    #[\Override]
    public function guessSorter($class, $property, $type)
    {
        return null;
    }

    #[\Override]
    public function guessFilter($class, $property, $type)
    {
        return null;
    }
}
