<?php

namespace Oro\Bundle\NoteBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\NoteBundle\Migrations\Schema\v1_0\OroNoteBundle;
use Oro\Bundle\NoteBundle\Migrations\Schema\v1_1\OroNoteBundle as NoteOrganization;
use Oro\Bundle\NoteBundle\Migrations\Schema\v1_2\OroNoteBundle as NoteAttachment;

class OroNoteBundleInstaller implements Installation, AttachmentExtensionAwareInterface
{
    /** @var AttachmentExtension */
    protected $attachmentExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroNoteBundle::addNoteTable($schema);
        NoteOrganization::addOrganizationFields($schema);
        NoteAttachment::addAttachment($schema, $this->attachmentExtension);
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_3';
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }
}
