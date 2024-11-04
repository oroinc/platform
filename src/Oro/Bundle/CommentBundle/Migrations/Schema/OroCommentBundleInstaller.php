<?php

namespace Oro\Bundle\CommentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareInterface;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCommentBundleInstaller implements
    Installation,
    CommentExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{
    use CommentExtensionAwareTrait;
    use AttachmentExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_2';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroCommentTable($schema);

        /** Foreign keys generation **/
        $this->addOroCommentForeignKeys($schema);

        $this->commentExtension->addCommentAssociation($schema, 'oro_email');
        $this->commentExtension->addCommentAssociation($schema, 'oro_note');
        $this->attachmentExtension->addFileRelation($schema, 'oro_comment', 'attachment');
    }

    private function createOroCommentTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_comment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('updated_by_id', 'integer', ['notnull' => false]);
        $table->addColumn('message', 'text');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('createdAt', 'datetime');
        $table->addColumn('updatedAt', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id']);
        $table->addIndex(['updated_by_id'], 'IDX_30E6463D2793CC5E');
        $table->addIndex(['organization_id'], 'IDX_30E6463D32C8A3DE');
    }

    private function addOroCommentForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_comment');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['updated_by_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
