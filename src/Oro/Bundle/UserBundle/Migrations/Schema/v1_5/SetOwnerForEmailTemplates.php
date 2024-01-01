<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SetOwnerForEmailTemplates implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addOwnerToEmailTemplateTable($schema);
        $queries->addQuery(new SetOwnerForEmailTemplatesQuery());
    }

    private function addOwnerToEmailTemplateTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_template');
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addIndex(['user_owner_id'], 'IDX_E62049DE9EB185F9');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
