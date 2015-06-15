<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateEmailOriginRelation implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addUserAndOrganizationColumns($schema, $queries);
    }

    public static function addUserAndOrganizationColumns(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $queries->addPostQuery('UPDATE oro_email_origin eo SET eo.user_id = (SELECT ueo.user_id FROM oro_user_email_origin ueo WHERE ueo.origin_id = eo.id)');
        $queries->addPostQuery('UPDATE oro_email_origin eo SET eo.organization_id = (SELECT u.organization_id FROM oro_user u WHERE u.user_id = eo.user_id)');
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 0;
    }
}
