<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

/**
 * Interface for email body attachments.
 */
interface EmailAttachmentLoaderInterface
{
    /**
     * Checks if this loader can be used to load an email attachments from the given email body.
     *
     * @param EmailOrigin $origin
     * @return bool
     */
    public function supports(EmailOrigin $origin);

    /**
     * Loads email attachments for the given email body
     * @param EmailBody $emailBody
     * @return array
     */
    public function loadEmailAttachments(EmailBody $emailBody);

    /**
     * Loads email attachment for the given email body and file name
     * @param EmailBody $emailBody
     * @param string $attachmentName
     * @return array|EmailAttachment
     */
    public function loadEmailAttachment(EmailBody $emailBody, string $attachmentName);
}
