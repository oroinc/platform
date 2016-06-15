<?php

namespace Oro\Bundle\NoteBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNoteBundle implements Migration, AttachmentExtensionAwareInterface
{
    /** @var AttachmentExtension */
    protected $attachmentExtension;

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
        self::addAttachment($schema, $this->attachmentExtension);
    }

    /**
     * @param Schema              $schema
     * @param AttachmentExtension $attachmentExtension
     */
    public static function addAttachment(Schema $schema, AttachmentExtension $attachmentExtension)
    {
        $attachmentExtension->addFileRelation(
            $schema,
            'oro_note',
            'attachment'
        );
    }
}
