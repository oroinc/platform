<?php


namespace Oro\Bundle\EmailBundle\Builder;

use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * A helper class allows you to easy build EmailBody entity
 */
class EmailBodyBuilder
{
    /**
     * @var EmailBody
     */
    private $emailBody = null;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager = null)
    {
        $this->configManager = $configManager;
    }

    /**
     * Gets built EmailBody entity
     *
     * @return EmailBody
     * @throws \LogicException
     */
    public function getEmailBody()
    {
        if ($this->emailBody === null) {
            throw new \LogicException('Call setEmailBody first.');
        }

        return $this->emailBody;
    }

    /**
     * Sets an email body properties
     *
     * @param string $content
     * @param bool   $bodyIsText
     */
    public function setEmailBody($content, $bodyIsText)
    {
        $this->emailBody = new EmailBody();
        $this->emailBody
            ->setBodyContent($content)
            ->setBodyIsText($bodyIsText);
    }

    /**
     * Adds an email attachment
     *
     * @param string      $fileName
     * @param string      $content
     * @param string      $contentType
     * @param string      $contentTransferEncoding
     * @param null|string $embeddedContentId
     *
     * @throws \LogicException
     */
    public function addEmailAttachment(
        $fileName,
        $content,
        $contentType,
        $contentTransferEncoding,
        $embeddedContentId = null
    ) {
        if (!$this->allowSaveAttachment(strlen(base64_decode($content)))) {
            return;
        }

        if ($this->emailBody === null) {
            throw new \LogicException('Call setEmailBody first.');
        }

        $emailAttachment        = new EmailAttachment();
        $emailAttachmentContent = new EmailAttachmentContent();

        $emailAttachmentContent
            ->setEmailAttachment($emailAttachment)
            ->setContentTransferEncoding($contentTransferEncoding)
            ->setContent($content);

        $emailAttachment
            ->setFileName($fileName)
            ->setContentType($contentType)
            ->setContent($emailAttachmentContent)
            ->setEmbeddedContentId($embeddedContentId);

        $this->emailBody->addAttachment($emailAttachment);
    }

    /**
     * Check enabled save and max allow size
     *
     * @param int $size - byte
     *
     * @return bool
     */
    protected function allowSaveAttachment($size) {
        // skipp attachment if disabled to save
        if ($this->configManager && !$this->configManager->get('oro_email.attachment_sync_enable')) {
            return false;
        }

        $attachmentSyncMaxSize = $this->configManager->get('oro_email.attachment_sync_max_size');
        // skipp save large attachemnt files
        if (
            $this->configManager
            && $attachmentSyncMaxSize !== 0 // unlimit
            && $size / 1024 / 1024 > $attachmentSyncMaxSize
        ) {
            return false;
        }

        return true;
    }
}
