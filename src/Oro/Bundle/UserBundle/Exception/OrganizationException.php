<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

class OrganizationException extends AccountStatusException
{
    #[\Override]
    public function getMessageKey(): string
    {
        return 'You don\'t have active organization assigned.';
    }
}
