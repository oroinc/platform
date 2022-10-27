<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;

/**
 * Guess primary keys columns as string for save original formatting
 */
class PrimaryKeyColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function guessFormatter($class, $property, $type): ?ColumnGuess
    {
        $metadata = $this->doctrine->getManagerForClass($class)->getClassMetadata($class);

        return \in_array($property, $metadata->getIdentifier(), true)
            ? new ColumnGuess(['frontend_type' => 'string'], ColumnGuess::MEDIUM_CONFIDENCE)
            : null;
    }
}
