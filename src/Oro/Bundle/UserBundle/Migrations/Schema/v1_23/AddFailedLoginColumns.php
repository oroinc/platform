<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_23;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddFailedLoginColumns implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::updateOroUserTable($schema);
    }

    /**
     * Add failed_login_count to User
     *
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function updateOroUserTable(Schema $schema)
    {
        $table = $schema->getTable('oro_user');
        $table->addColumn('failed_login_count', 'integer', ['default' => '0', 'precision' => 0, 'unsigned' => true]);
    }
}
