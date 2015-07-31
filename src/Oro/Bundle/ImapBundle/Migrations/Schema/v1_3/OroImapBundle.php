<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroImapBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Update Email Origin Name */
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_email_origin SET name = :new_name WHERE name = :old_name',
                ['new_name' => 'useremailorigin', 'old_name' => 'imapemailorigin'],
                ['new_name' => Type::STRING, 'old_name' => Type::STRING]
            )
        );

        /** Tables generation **/
        self::addSmtpFieldsToOroEmailOriginTable($schema);
    }

    /**
     * Add Smtp fields to the oro_email_origin table
     *
     * @param Schema $schema
     */
    public static function addSmtpFieldsToOroEmailOriginTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('smtp_host', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('smtp_port', 'integer', ['notnull' => false]);
        $table->addColumn('smtp_encryption', 'string', ['notnull' => false, 'length' => 3]);
    }
}
