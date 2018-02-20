<?php

namespace Oro\Bundle\CommentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
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
    use AttachmentExtensionAwareTrait;

    /** @var CommentExtension */
    protected $comment;

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
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroCommentBundle10::createCommentTable($schema);
        OroCommentBundle10::addCommentToEmail($schema, $this->comment);
        OroCommentBundle10::addCommentToNote($schema, $this->comment);
        OroCommentBundle10::addAttachment($schema, $this->attachmentExtension);
    }
}
