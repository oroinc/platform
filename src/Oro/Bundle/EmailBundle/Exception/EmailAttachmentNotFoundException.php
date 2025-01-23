<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Entity\Email;

/**
 * This exception is thrown when we try to download email attachment, but attachment not found
 */
class EmailAttachmentNotFoundException extends \RuntimeException
{
    public function __construct(Email $email)
    {
        parent::__construct(sprintf('Cannot find a attachments for "%s" email.', $email->getSubject()));
    }
}
