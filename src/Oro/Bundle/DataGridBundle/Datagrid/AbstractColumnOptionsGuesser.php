<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

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
