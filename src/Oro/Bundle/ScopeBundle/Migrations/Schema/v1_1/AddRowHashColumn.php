<?php

namespace Oro\Bundle\ScopeBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ScopeBundle\Migration\Query\AddScopeUniquenessQuery;
use Oro\Bundle\ScopeBundle\Migration\Query\AddTriggerToRowHashQuery;

/**
 * Add row_hash field for the oro_scope table to use it in unique constraint.
 */
class AddRowHashColumn implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_scope');
        if (!$table->hasColumn('row_hash')) {
            $table->addColumn('row_hash', 'string', ['length' => 32, 'notnull' => false]);
        }

        // Fill hash and remove duplicates
        $queries->addQuery(new AddScopeUniquenessQuery());
        $queries->addQuery(new AddTriggerToRowHashQuery());
        $this->addRowHashComment($table);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addRowHashComment(Table $table): void
    {
        $columns = [];
        foreach ($table->getForeignKeys() as $relation) {
            $columns[] = strtolower($relation->getLocalColumns()[0]);
        }
        sort($columns);

        $comment = implode(',', $columns);
        $table->getColumn('row_hash')->setComment($comment);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder(): int
    {
        return 0;
    }
}
