<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer;

/**
 * Provides a way to get email attachment models are related to a specific entity.
 */
class EmailAttachmentProvider
{
    /** @var EmailThreadProvider */
    private $emailThreadProvider;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var AttachmentProvider */
    private $attachmentProvider;

    /** @var EmailAttachmentTransformer */
    private $emailAttachmentTransformer;

    public function __construct(
        EmailThreadProvider $emailThreadProvider,
        ManagerRegistry $doctrine,
        AttachmentProvider $attachmentProvider,
        EmailAttachmentTransformer $emailAttachmentTransformer
    ) {
        $this->emailThreadProvider = $emailThreadProvider;
        $this->doctrine = $doctrine;
        $this->attachmentProvider = $attachmentProvider;
        $this->emailAttachmentTransformer = $emailAttachmentTransformer;
    }

    /**
     * @param Email $emailEntity
     *
     * @return EmailAttachmentModel[]
     */
    public function getThreadAttachments(Email $emailEntity): array
    {
        $emailAttachmentModels = [];
        $threadEmails = $this->emailThreadProvider->getThreadEmails(
            $this->doctrine->getManager(),
            $emailEntity
        );
        foreach ($threadEmails as $threadEmail) {
            if ($threadEmail->getEmailBody() && $threadEmail->getEmailBody()->getHasAttachments()) {
                $emailAttachments = $threadEmail->getEmailBody()->getAttachments();
                foreach ($emailAttachments as $emailAttachment) {
                    $emailAttachmentModels[] = $this->emailAttachmentTransformer->entityToModel($emailAttachment);
                }
            }
        }

        return $emailAttachmentModels;
    }

    /**
     * @param object $entity
     *
     * @return EmailAttachmentModel[]
     */
    public function getScopeEntityAttachments(object $entity): array
    {
        $emailAttachmentModels = [];
        $attachments = $this->attachmentProvider->getEntityAttachments($entity);
        foreach ($attachments as $attachment) {
            $emailAttachmentModels[] = $this->emailAttachmentTransformer->attachmentEntityToModel($attachment);
        }

        return $emailAttachmentModels;
    }
}
