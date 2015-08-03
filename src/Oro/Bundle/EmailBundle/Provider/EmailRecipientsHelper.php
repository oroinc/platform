<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Closure;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;

class EmailRecipientsHelper
{
    /**
     * @param EmailRecipientsProviderArgs $providerArgs
     * @return Closure
     */
    public static function createRecipientsFilter(EmailRecipientsProviderArgs $providerArgs)
    {
        $query = $providerArgs->getQuery();
        $excludedEmails = $providerArgs->getExcludedEmails();

        return function ($email) use ($excludedEmails, $query) {
            return !in_array($email, $excludedEmails) && stripos($email, $query) !== false;
        };
    }
}
