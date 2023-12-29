<?php

namespace Oro\Bundle\CommentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareInterface;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareTrait;
use Oro\Bundle\CommentBundle\Migrations\Schema\v1_0\OroCommentBundle as OroCommentBundle10;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCommentBundleInstaller implements
    Installation,
    CommentExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{
    use CommentExtensionAwareTrait;
    use AttachmentExtensionAwareTrait;

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
        OroCommentBundle10::addCommentToEmail($schema, $this->commentExtension);
        OroCommentBundle10::addCommentToNote($schema, $this->commentExtension);
        OroCommentBundle10::addAttachment($schema, $this->attachmentExtension);
    }
}
