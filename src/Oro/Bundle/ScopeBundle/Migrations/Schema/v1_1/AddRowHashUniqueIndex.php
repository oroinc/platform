<?php

namespace Oro\Bundle\ScopeBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds unique constraint by relations_hash field.
 */
class AddRowHashUniqueIndex implements Migration, OrderedMigrationInterface
{
    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_scope');
        if (!$table->hasIndex('oro_scope_row_hash_uidx')) {
            $table->addUniqueIndex(['row_hash'], 'oro_scope_row_hash_uidx');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder(): int
    {
        return 10;
    }
}
