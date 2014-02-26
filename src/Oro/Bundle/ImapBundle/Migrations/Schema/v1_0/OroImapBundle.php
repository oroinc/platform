<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroImapBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_email_folder_imap **/
        $table = $schema->createTable('oro_email_folder_imap');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('folder_id', 'integer', []);
        $table->addColumn('uid_validity', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['folder_id'], 'UNIQ_EC4034F9162CB942');
        /** End of generate table oro_email_folder_imap **/

        /** Generate table oro_email_imap **/
        $table = $schema->createTable('oro_email_imap');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email_id', 'integer', []);
        $table->addColumn('uid', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email_id'], 'UNIQ_17E00D83A832C1C9');
        /** End of generate table oro_email_imap **/

        /** Generate foreign keys for table oro_email_folder_imap **/
        $table = $schema->getTable('oro_email_folder_imap');
        $table->addForeignKeyConstraint($schema->getTable('oro_email_folder'), ['folder_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_email_folder_imap **/

        /** Generate foreign keys for table oro_email_imap **/
        $table = $schema->getTable('oro_email_imap');
        $table->addForeignKeyConstraint($schema->getTable('oro_email'), ['email_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_email_imap **/

        /** Add Imap fields to the oro_email_origin table **/
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('imap_host', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('imap_port', 'integer', ['notnull' => false]);
        $table->addColumn('imap_ssl', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('imap_user', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('imap_password', 'string', ['notnull' => false, 'length' => 100]);

        // @codingStandardsIgnoreEnd

        return [];
    }
}
