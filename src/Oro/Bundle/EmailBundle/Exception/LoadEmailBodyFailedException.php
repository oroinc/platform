<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Entity\Email;

/**
 * Thrown when loading an email body fails with an error.
 *
 * This exception is raised when an attempt to load an email body encounters an error,
 * typically from an external email server or storage system, with details about the failure reason.
 */
class LoadEmailBodyFailedException extends LoadEmailBodyException
{
    public function __construct(Email $email, ?\Exception $previous = null)
    {
        $message = sprintf('Cannot load a body for "%s" email.', $email->getSubject());
        if ($previous) {
            $message .= sprintf(' Reason: %s.', $previous->getMessage());
        }
        parent::__construct($message, 0, $previous);
    }
}
