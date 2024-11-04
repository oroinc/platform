<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

class CredentialsResetException extends AccountStatusException
{
    #[\Override]
    public function getMessageKey(): string
    {
        return 'Your password was reset by administrator. Please, check your email for details.';
    }
}
