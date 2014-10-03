<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;

/**
 * Special ColumnOptionsGuesserInterface mock used for testing purposes.
 */
class ColumnOptionsGuesserMock implements ColumnOptionsGuesserInterface
{
    /**
     * {@inheritdoc}
     */
    public function guessFormatter($class, $property, $type)
    {
        $frontendType = $type;
        switch ($type) {
            case 'smallint':
            case 'bigint':
                $frontendType = 'integer';
                break;
            case 'float':
                $frontendType = 'decimal';
                break;
            case 'money':
                $frontendType = 'currency';
                break;
        }

        return new ColumnGuess(['frontend_type' => $frontendType], ColumnGuess::LOW_CONFIDENCE);
    }

    /**
     * {@inheritdoc}
     */
    public function guessSorter($class, $property, $type)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessFilter($class, $property, $type)
    {
        $filterType = $type;
        switch ($type) {
            case 'integer':
            case 'smallint':
            case 'bigint':
            case 'decimal':
            case 'float':
            case 'money':
                $filterType = 'number';
                break;
        }

        return new ColumnGuess(['type' => $filterType], ColumnGuess::LOW_CONFIDENCE);
    }
}
