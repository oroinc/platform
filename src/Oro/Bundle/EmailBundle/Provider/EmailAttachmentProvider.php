<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Form\Model\Factory;
use Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer;

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
     * @var EmailAttachmentTransformer
     */
    protected $emailAttachmentTransformer;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @param EmailThreadProvider        $emailThreadProvider
     * @param EntityManager              $entityManager
     * @param AttachmentProvider         $attachmentProvider
     * @param EmailAttachmentTransformer $emailAttachmentTransformer
     */
    public function __construct(
        EmailThreadProvider $emailThreadProvider,
        EntityManager $entityManager,
        AttachmentProvider $attachmentProvider,
        EmailAttachmentTransformer $emailAttachmentTransformer
    ) {
        $this->emailThreadProvider        = $emailThreadProvider;
        $this->em                         = $entityManager;
        $this->attachmentProvider         = $attachmentProvider;
        $this->emailAttachmentTransformer = $emailAttachmentTransformer;
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
            if ($threadEmail->getEmailBody() && $threadEmail->getEmailBody()->getHasAttachments()) {
                $emailAttachments = $threadEmail->getEmailBody()->getAttachments();

                foreach ($emailAttachments as $emailAttachment) {
                    $attachments[] = $this->emailAttachmentTransformer->entityToModel($emailAttachment);
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
            $attachments[] = $this->emailAttachmentTransformer->oroToModel($oroAttachment);
        }

        return $attachments;
    }
}
