<?php

namespace Oro\Bundle\CommentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtension;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareInterface;
use Oro\Bundle\CommentBundle\Migrations\Schema\v1_0\OroCommentBundle as OroCommentBundle10;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCommentBundleInstaller implements
    Installation,
    CommentExtensionAwareInterface,
    AttachmentExtensionAwareInterface
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
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroCommentBundle10::createCommentTable($schema);
        OroCommentBundle10::addCommentToEmail($schema, $this->comment);
        OroCommentBundle10::addCommentToCalendarEvent($schema, $this->comment);
        OroCommentBundle10::addCommentToNote($schema, $this->comment);
        OroCommentBundle10::addAttachment($schema, $this->attachmentExtension);
    }
}
