<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Schema\Table;

trait MigrationConstraintTrait
{
    /**
     * @param Table $table
     * @param string $columnName
     * @return string
     * @throws \LogicException
     */
    protected function getConstraintName(Table $table, $columnName)
    {
        $foreignKeys = $table->getForeignKeys();
        foreach ($foreignKeys as $key) {
            if ($key->getLocalColumns() === [$columnName]) {
                return $key->getName();
            }
        }

        throw new \LogicException(
            sprintf('No constraint found for column %s in table %s', $columnName, $table->getName())
        );
    }
}
