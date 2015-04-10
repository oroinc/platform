<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as AttachmentModel;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;

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
     * @param EmailThreadProvider $emailThreadProvider
     * @param EntityManager       $entityManager
     * @param AttachmentProvider  $attachmentProvider
     */
    public function __construct(
        EmailThreadProvider $emailThreadProvider,
        EntityManager $entityManager,
        AttachmentProvider $attachmentProvider
    ) {
        $this->emailThreadProvider = $emailThreadProvider;
        $this->em                  = $entityManager;
        $this->attachmentProvider  = $attachmentProvider;
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

            $attachments[] = $attachmentModel;
        }

        return $attachments;
    }

    /**
     * @return array
     */
    public function getFreeAttachments()
    {
        $attachments = [];
        $repo = $this->em->getRepository('OroEmailBundle:EmailAttachment');

        $emailAttachments = $repo->findBy([
            'emailBody' => null,
        ]);
        foreach ($emailAttachments as $emailAttachment) {
            $attachments[] = $this->emailAttachmentToAttachmentModel($emailAttachment);
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

        return $attachmentModel;
    }
}
