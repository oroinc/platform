<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Oro\Bundle\AttachmentBundle\Entity\Attachment as AttachmentOro;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment as AttachmentEntity;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as AttachmentModel;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

/**
 * Class EmailAttachmentTransformer
 *
 * @package Oro\Bundle\EmailBundle\Tools
 */
class EmailAttachmentTransformer
{
    /**
     * @var DateTimeFormatter
     */
    protected $dateTimeFormatter;

    /**
     * @param DateTimeFormatter $dateTimeFormatter
     */
    public function __construct(DateTimeFormatter $dateTimeFormatter)
    {
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * @param AttachmentEntity $attachmentEntity
     *
     * @return AttachmentModel
     */
    protected function entityToModel(AttachmentEntity $attachmentEntity)
    {
        $attachmentModel = new AttachmentModel();

        $attachmentModel->setEmailAttachment($attachmentEntity);
        $attachmentModel->setType(AttachmentModel::TYPE_EMAIL_ATTACHMENT);
        $attachmentModel->setId($attachmentEntity->getId());
        $attachmentModel->setFileSize(strlen($attachmentEntity->getContent()->getContent()));
        $attachmentModel->setModified($this->dateTimeFormatter->format(new \DateTime('now'))); // todo what value here?

        return $attachmentModel;
    }
}
