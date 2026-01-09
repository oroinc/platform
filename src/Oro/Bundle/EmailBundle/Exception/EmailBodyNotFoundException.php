<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Entity\Email;

/**
 * Thrown when an email body cannot be found in storage.
 *
 * This exception is raised when attempting to load or access an email body that does not exist
 * in the database, typically during email synchronization or retrieval operations.
 */
class EmailBodyNotFoundException extends LoadEmailBodyException
{
    public function __construct(Email $email)
    {
        parent::__construct(sprintf('Cannot find a body for "%s" email.', $email->getSubject()));
    }
}
