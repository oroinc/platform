<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v3_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroImapBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Update Email Origin Name */
        $queries->addPreQuery("UPDATE oro_email_origin SET name='useremailorigin' WHERE name='imapemailorigin';");

        /** Tables generation **/
        $this->addSmtpFieldsToOroEmailOriginTable($schema);
    }

    /**
     * Add Smtp fields to the oro_email_origin table
     *
     * @param Schema $schema
     */
    protected function addSmtpFieldsToOroEmailOriginTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('smtp_host', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('smtp_port', 'integer', ['notnull' => false]);
    }
}
