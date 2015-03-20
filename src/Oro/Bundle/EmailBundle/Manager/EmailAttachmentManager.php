<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;

/**
 * Class EmailAttachmentManager
 *
 * @package Oro\Bundle\EmailBundle\Manager
 */
class EmailAttachmentManager
{
    /**
     * @var EmailCacheManager
     */
    protected $emailCacheManager;

    /**
     * @var AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @param EmailCacheManager $emailCacheManager
     * @param AttachmentManager $oroAttachmentManager
     */
    public function __construct(
        EmailCacheManager $emailCacheManager,
        AttachmentManager $oroAttachmentManager
    ) {
        $this->emailCacheManager = $emailCacheManager;
        $this->attachmentManager = $oroAttachmentManager;
    }

    /**
     * @param Email $email
     * @param       $entity
     * @throws LoadEmailBodyException
     */
    public function linkEmailAttachmentsToEntity(Email $email, $entity)
    {
        $this->emailCacheManager->ensureEmailBodyCached($email);

        if (!$email->getEmailBody()->getHasAttachments()) {
            return;
        }

        $emailAttachments = $email->getEmailBody()->getAttachments();
        foreach ($emailAttachments as $emailAttachment) {
            $attachment = $emailAttachment->getAttachment() ?: new Attachment();
            if ($attachment->supportTarget($entity)) {
                if ($attachment->getId() == null) {
                    $this->createAttachmentFromDB($attachment, $emailAttachment);
                }
            }
        }
    }

    protected function createAttachmentFromDB(Attachment $attachment, EmailAttachment $emailAttachment)
    {
        //$file = new File();
    }
}
