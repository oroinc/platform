<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addEmailReference($schema);
        self::addPostQuery($queries);
    }

    /**
     * @param Schema   $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function addEmailReference(Schema $schema)
    {
        $emailTable = $schema->getTable('oro_email');
        $emailBodyTable = $schema->getTable('oro_email_body');

        $emailTable->addColumn('email_body_id', 'integer', ['notnull' => false]);
        $emailTable->addForeignKeyConstraint(
            $emailBodyTable,
            ['email_body_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            'FK_2A30C17126A2754B'
        );
        $emailTable->addIndex(['email_body_id'], 'IDX_2A30C17126A2754B');
    }

    public static function addPostQuery(QueryBag $queries)
    {
        $queries->addPostQuery(
            'UPDATE oro_email e LEFT JOIN oro_email_body b ON e.id = b.email_id SET e.email_body_id = b.id'
        );
    }
}
