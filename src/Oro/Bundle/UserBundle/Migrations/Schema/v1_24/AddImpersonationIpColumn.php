<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddImpersonationIpColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addColumn($schema);
    }

    /**
     * Add ip_address to Impersonation
     *
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function addColumn(Schema $schema)
    {
        $table = $schema->getTable('oro_user_impersonation');
        $table->addColumn(
            'ip_address',
            'string',
            [
                'length' => 255,
                'nullable' => false,
                'default' => '127.0.0.1'
            ]
        );
    }
}
