<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migrations\Schema\SetOwnershipTypeQuery;

class OroEmbeddedFormBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addOwner($schema);
        self::setOwnership($queries);
    }

    /**
     * Adds owner_id field
     *
     * @param Schema $schema
     */
    public static function addOwner(Schema $schema)
    {
        $table = $schema->getTable('oro_embedded_form');

        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_id'], 'IDX_F7A34C17E3C61F9', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Set ownership type for EmbeddedForm entity to Organization
     *
     * @param QueryBag $queries
     */
    public static function setOwnership(QueryBag $queries)
    {
        $queries->addQuery(
            new SetOwnershipTypeQuery('Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm')
        );
    }
}
