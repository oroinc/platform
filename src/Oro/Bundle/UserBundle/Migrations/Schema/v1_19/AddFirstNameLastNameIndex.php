<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_19;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddFirstNameLastNameIndex implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addFirstNameLastNameIndex($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function addFirstNameLastNameIndex(Schema $schema)
    {
        // Adding these index due to issue with mysql 5.6 version @see CRM-5117
        $table = $schema->getTable('oro_user');
        $table->addIndex(['first_name', 'last_name'], 'user_first_name_last_name_idx');
    }
}
