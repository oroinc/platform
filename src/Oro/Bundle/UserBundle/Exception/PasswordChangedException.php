<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

class PasswordChangedException extends AccountStatusException
{
    #[\Override]
    public function getMessageKey(): string
    {
        return 'Password has been changed.';
    }
}
