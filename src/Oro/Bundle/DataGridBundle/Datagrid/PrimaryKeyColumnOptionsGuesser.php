<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;

/**
 * Guess primary keys columns as string for save original formatting
 */
class PrimaryKeyColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    /** @var Registry */
    private $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function guessFormatter($class, $property, $type): ?ColumnGuess
    {
        $metadata = $this->registry->getManagerForClass($class)->getClassMetadata($class);

        return \in_array($property, $metadata->getIdentifier(), true)
            ? new ColumnGuess(['frontend_type' => 'string'], ColumnGuess::MEDIUM_CONFIDENCE)
            : null;
    }
}
