<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Entity\Email;

class LoadEmailBodyFailedException extends LoadEmailBodyException
{
    /**
     * @param Email  $email
     * @param \Exception $previous
     */
    public function __construct(Email $email, \Exception $previous = null)
    {
        $message = sprintf('Cannot load a body for "%s" email.', $email->getSubject());
        if ($previous) {
            $message .= sprintf(' Reason: %s.', $previous->getMessage());
        }
        parent::__construct($message, 0, $previous);
    }
}
