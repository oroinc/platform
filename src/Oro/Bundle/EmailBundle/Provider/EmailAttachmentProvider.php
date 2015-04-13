<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as AttachmentModel;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class EmailAttachmentProvider
{
    /**
     * @var EmailThreadProvider
     */
    protected $emailThreadProvider;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var AttachmentProvider
     */
    protected $attachmentProvider;

    /**
     * @var DateTimeFormatter
     */
    protected $dateTimeFormatter;

    /**
     * @param EmailThreadProvider $emailThreadProvider
     * @param EntityManager       $entityManager
     * @param AttachmentProvider  $attachmentProvider
     * @param DateTimeFormatter   $dateTimeFormatter
     */
    public function __construct(
        EmailThreadProvider $emailThreadProvider,
        EntityManager $entityManager,
        AttachmentProvider $attachmentProvider,
        DateTimeFormatter $dateTimeFormatter
    ) {
        $this->emailThreadProvider = $emailThreadProvider;
        $this->em                  = $entityManager;
        $this->attachmentProvider  = $attachmentProvider;
        $this->dateTimeFormatter   = $dateTimeFormatter;
    }

    /**
     * @param Email $emailEntity
     *
     * @return array
     */
    public function getThreadAttachments(Email $emailEntity)
    {
        $attachments = [];
        $threadEmails = $this->emailThreadProvider->getThreadEmails($this->em, $emailEntity);

        /** @var Email $threadEmail */
        foreach ($threadEmails as $threadEmail) {
            if ($threadEmail->getEmailBody()->getHasAttachments()) {
                $emailAttachments = $emailEntity->getEmailBody()->getAttachments();

                foreach ($emailAttachments as $emailAttachment) {
                    $attachments[] = $this->emailAttachmentToAttachmentModel($emailAttachment);
                }
            }
        }

        return $attachments;
    }

    /**
     * @param $entity
     *
     * @return array
     */
    public function getScopeEntityAttachments($entity)
    {
        $attachments = [];
        $oroAttachments = $this->attachmentProvider->getEntityAttachments($entity);

        foreach ($oroAttachments as $oroAttachment) {
            $attachmentModel = new AttachmentModel();
            $attachmentModel->setType(AttachmentModel::TYPE_ATTACHMENT);
            $attachmentModel->setId($oroAttachment->getId());
            $attachmentModel->setFileName($oroAttachment->getFile()->getOriginalFilename());
            $attachmentModel->setFileSize($oroAttachment->getFile()->getFileSize());
            $attachmentModel->setModified($this->dateTimeFormatter->format(
                $oroAttachment->getCreatedAt()
            ));

            $attachments[] = $attachmentModel;
        }

        return $attachments;
    }

    /**
     * @param EmailAttachment $emailAttachment
     *
     * @return AttachmentModel
     */
    protected function emailAttachmentToAttachmentModel(EmailAttachment $emailAttachment)
    {
        $attachmentModel = new AttachmentModel();
        $attachmentModel->setEmailAttachment($emailAttachment);
        $attachmentModel->setType(AttachmentModel::TYPE_EMAIL_ATTACHMENT);
        $attachmentModel->setId($emailAttachment->getId());
        $attachmentModel->setFileSize(strlen($emailAttachment->getContent()->getContent()));
        $attachmentModel->setModified($this->dateTimeFormatter->format(new \DateTime('now'))); // todo now?

        return $attachmentModel;
    }
}
