<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;


class EmailAttachmentProvider
{
    /**
     * @var EmailthreadProvider
     */
    protected $emailThreadProvider;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param EmailThreadProvider $emailThreadProvider
     * @param EntityManager       $entityManager
     */
    public function __construct(EmailThreadProvider $emailThreadProvider, EntityManager $entityManager)
    {
        $this->emailThreadProvider = $emailThreadProvider;
        $this->em                  = $entityManager;
    }

    /**
     * Retrieves list of attachments available for attaching to Email
     */
    public function getAvailableAttachmentList(Email $emailEntity)
    {
        // todo should return EmailAttachmentModel list
        return array_merge(
            $this->getFreeAttachments(),
            $this->getThreadAttachments($emailEntity),
            $this->getFreeAttachments()
        );
    }

    protected function getThreadAttachments(Email $emailEntity)
    {
        $attachments = [];
        $threadEmails = $this->emailThreadProvider->getThreadEmails($this->em, $emailEntity);

        /** @var Email $threadEmail */
        foreach ($threadEmails as $threadEmail) {
            if ($threadEmail->getEmailBody()->getHasAttachments()) {
                $attachments = array_merge($emailEntity->getEmailBody()->getAttachments()->toArray());
            }
        }

        return $attachments;
    }

    protected function getScopeEntityAttachments()
    {
        return [];
    }

    protected function getFreeAttachments()
    {
        return [];
    }
}
