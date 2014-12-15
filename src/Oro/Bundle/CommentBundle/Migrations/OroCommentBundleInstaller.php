<?php

namespace Oro\Bundle\CommentBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtension;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\CommentBundle\Migrations\Schema\v1_0\OroCommentBundle as OroCommentBundle10;

class OroCommentBundleInstaller implements Installation, CommentExtensionAwareInterface
{
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
        return 'v1_0';
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
    }
}
