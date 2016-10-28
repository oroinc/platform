<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddPasswordExpiresAtColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::updateOroUserTable($schema);
        $queries->addPostQuery(new FillPasswordExpiresAtField('oro_user'));
    }

    /**
     * Add password_expires_at to User
     *
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function updateOroUserTable(Schema $schema)
    {
        $table = $schema->getTable('oro_user');
        $table->addColumn('password_expires_at', 'datetime', ['notnull' => false]);
    }
}
