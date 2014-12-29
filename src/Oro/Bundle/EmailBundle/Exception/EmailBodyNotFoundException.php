<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Entity\Email;

class EmailBodyNotFoundException extends LoadEmailBodyException
{
    /**
     * @param Email $email
     */
    public function __construct(Email $email)
    {
        parent::__construct(sprintf('Cannot find a body for "%s" email.', $email->getSubject()));
    }
}
