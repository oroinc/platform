<?php

namespace Oro\Bundle\CommentBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtension;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCommentBundle implements Migration, CommentExtensionAwareInterface, AttachmentExtensionAwareInterface
{
    /** @var CommentExtension */
    protected $comment;

    /** @var AttachmentExtension */
    protected $attachmentExtension;

    /**
     * @param CommentExtension $commentExtension
     */
    public function setCommentExtension(CommentExtension $commentExtension)
    {
        $this->comment = $commentExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createCommentTable($schema);
        self::addCommentToEmail($schema, $this->comment);
        self::addCommentToCalendarEvent($schema, $this->comment);
        self::addCommentToNote($schema, $this->comment);
        self::addAttachment($schema, $this->attachmentExtension);
    }

    /**
     * @param Schema $schema
     *
     * @throws SchemaException
     */
    public static function createCommentTable(Schema $schema)
    {
        $table = $schema->createTable('oro_comment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('updated_by_id', 'integer', ['notnull' => false]);
        $table->addColumn('message', 'text');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('updatedAt', 'datetime', []);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id']);
        $table->addIndex(['updated_by_id'], 'IDX_30E6463D2793CC5E', []);
        $table->addIndex(['organization_id'], 'IDX_30E6463D32C8A3DE', []);

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

    /**
     * @param Schema           $schema
     * @param CommentExtension $commentExtension
     */
    public static function addCommentToEmail(Schema $schema, CommentExtension $commentExtension)
    {
        $commentExtension->addCommentAssociation($schema, 'oro_email');
    }

    /**
     * @param Schema           $schema
     * @param CommentExtension $commentExtension
     */
    public static function addCommentToCalendarEvent(Schema $schema, CommentExtension $commentExtension)
    {
        $commentExtension->addCommentAssociation($schema, 'oro_calendar_event');
    }

    /**
     * @param Schema           $schema
     * @param CommentExtension $commentExtension
     */
    public static function addCommentToNote(Schema $schema, CommentExtension $commentExtension)
    {
        $commentExtension->addCommentAssociation($schema, 'oro_note');
    }

    /**
     * @param Schema              $schema
     * @param AttachmentExtension $attachmentExtension
     */
    public static function addAttachment(Schema $schema, AttachmentExtension $attachmentExtension)
    {
        $attachmentExtension->addFileRelation(
            $schema,
            'oro_comment',
            'attachment'
        );
    }
}
