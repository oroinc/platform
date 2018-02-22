<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_33;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * ORO Migration that adds cascade deletions to email-related tables
 */
class AddCascadeDeletionMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $emailAttachmentTable = $schema->getTable('oro_email_attachment');
        $emailAttachmentTable->removeForeignKey('fk_f4427f239b621d84');
        $emailAttachmentTable->addForeignKeyConstraint(
            $schema->getTable('oro_email_body'),
            ['body_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $emailAttachmentContentTable = $schema->getTable('oro_email_attachment_content');
        $emailAttachmentContentTable->removeForeignKey('fk_18704959464e68b');
        $emailAttachmentContentTable->addForeignKeyConstraint(
            $schema->getTable('oro_email_attachment'),
            ['attachment_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $emailFolderTable = $schema->getTable('oro_email_folder');
        $emailFolderTable->removeForeignKey('fk_oro_email_folder_origin_id');
        $emailFolderTable->addForeignKeyConstraint(
            $schema->getTable('oro_email_origin'),
            ['origin_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $emailUserTable = $schema->getTable('oro_email_user');
        $emailUserTable->removeForeignKey('fk_oro_email_user_origin_id');
        $emailUserTable->addForeignKeyConstraint(
            $schema->getTable('oro_email_origin'),
            ['origin_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
