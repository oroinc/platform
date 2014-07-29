<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SetOwnerForEmailTemplates implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addOwnerToOroEmailTemplate($schema);
        $queries->addQuery(new SetOwnerForEmailTemplatesQuery());
    }

    /**
     * Add owner to table oro_email_template
     *
     * @param Schema $schema
     */
    public static function addOwnerToOroEmailTemplate(Schema $schema)
    {
        /** Add user as owner to oro_email_template table **/
        $table = $schema->getTable('oro_email_template');
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addIndex(['user_owner_id'], 'IDX_E62049DE9EB185F9', []);

        /** Generate foreign keys for table oro_email_template **/
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
