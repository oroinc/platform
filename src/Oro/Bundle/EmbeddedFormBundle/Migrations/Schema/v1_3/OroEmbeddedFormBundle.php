<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migrations\Schema\SetOwnershipTypeQuery;

class OroEmbeddedFormBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addOwner($schema);
        $this->setOwnership($queries);
    }

    private function addOwner(Schema $schema): void
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

    private function setOwnership(QueryBag $queries): void
    {
        $queries->addQuery(new SetOwnershipTypeQuery('Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm'));
    }
}
