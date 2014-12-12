<?php

namespace Oro\Bundle\NoteBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtension;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migrations\Schema\v1_0\OroNoteBundle;
use Oro\Bundle\NoteBundle\Migrations\Schema\v1_1\OroNoteBundle as NoteOrganization;
use Oro\Bundle\NoteBundle\Migrations\Schema\v1_2\OroNoteBundle as NoteComments;

class OroNoteBundleInstaller implements Installation, CommentExtensionAwareInterface
{
    /** @var CommentExtension */
    protected $comment;

    /**
     * @param CommentExtension $commentExtension
     */
    public function setNoteExtension(CommentExtension $commentExtension)
    {
        $this->comment = $commentExtension;
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
        OroNoteBundle::addNoteTable($schema);
        NoteOrganization::addOrganizationFields($schema);
        NoteComments::addCommentAssociations($schema, $this->comment);
    }
}
