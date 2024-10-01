<?php

namespace Oro\Bundle\NoteBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNoteBundleInstaller implements Installation, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_3';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroNoteTable($schema);

        /** Foreign keys generation **/
        $this->addOroNoteForeignKeys($schema);

        $this->attachmentExtension->addFileRelation($schema, 'oro_note', 'attachment');
    }

    private function createOroNoteTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_note');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('updated_by_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('message', 'text');
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_owner_id'], 'IDX_BA066CE19EB185F9');
        $table->addIndex(['organization_id'], 'IDX_BA066CE132C8A3DE');
        $table->addIndex(['updated_by_user_id'], 'IDX_BA066CE12793CC5E');
    }

    private function addOroNoteForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_note');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['updated_by_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
