<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * ORO Migration that adds cascade deletions to imap-email-related tables
 */
class AddCascadeDeletionsMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $imapEmailTable = $schema->getTable('oro_email_imap');
        $imapEmailTable->removeForeignKey('fk_oro_email_imap_email_id');
        $imapEmailTable->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $imapEmailTable->removeForeignKey('FK_17E00D834F00B133');
        $imapEmailTable->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder_imap'),
            ['imap_folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_17E00D834F00B133'
        );

        $imapEmailFolderTable = $schema->getTable('oro_email_folder_imap');
        $imapEmailFolderTable->removeForeignKey('fk_ec4034f9162cb942');
        $imapEmailFolderTable->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
