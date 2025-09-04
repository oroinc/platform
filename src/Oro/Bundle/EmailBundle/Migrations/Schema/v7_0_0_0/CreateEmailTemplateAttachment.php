<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v7_0_0_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateEmailTemplateAttachment implements Migration, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($schema->hasTable('oro_email_template_attachment')) {
            // The table already exists, no need to create it again.
            return;
        }

        $this->createOroEmailTemplateAttachmentTable($schema);
        $this->addOroEmailTemplateAttachmentForeignKeys($schema);
        $this->addOroEmailTemplateAttachmentsFallbackColumn($schema);
    }

    /**
     * Create oro_email_template_attachment table.
     */
    private function createOroEmailTemplateAttachmentTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_template_attachment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('template_id', 'integer', ['notnull' => false]);
        $table->addColumn('translation_id', 'integer', ['notnull' => false]);
        $table->addColumn('file_placeholder', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);

        $this->attachmentExtension->addFileRelation(
            $schema,
            'oro_email_template_attachment',
            'file',
            [
                'attachment' => ['acl_protected' => true, 'file_applications' => ['default'], 'use_dam' => false],
                'view' => ['immutable' => true, 'is_displayable' => false],
                'form' => ['immutable' => true, 'is_enabled' => false],
                'email' => [
                    'immutable' => true,
                    'available_in_template' => false,
                ],
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['persist', 'remove'],
                    'without_default' => true,
                    'on_delete' => 'SET NULL',
                ],
            ],
            10
        );
    }

    private function addOroEmailTemplateAttachmentsFallbackColumn(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_template_localized');
        if ($table->hasColumn('attachments_fallback')) {
            return;
        }

        $table->addColumn('attachments_fallback', 'boolean', ['notnull' => true, 'default' => true]);
    }

    /**
     * Add oro_email_template_attachment foreign keys.
     */
    private function addOroEmailTemplateAttachmentForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_template_attachment');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_template'),
            ['template_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_template_localized'),
            ['translation_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['file_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
