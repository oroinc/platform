<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;

class EmailRecipientsHelper
{
    /**
     * @param EmailRecipientsProviderArgs $args
     * @param array $recipients
     *
     * @return array
     */
    public static function filterRecipients(EmailRecipientsProviderArgs $args, array $recipients)
    {
        $unExcludedEmails = array_filter(array_keys($recipients), function ($email) use ($args) {
            return !in_array($email, $args->getExcludedEmails());
        });

        $unExcludedRecipients = array_intersect_key($recipients, array_flip($unExcludedEmails));
        return array_filter($unExcludedRecipients, function ($email) use ($args) {
            return stripos($email, $args->getQuery()) !== false;
        });
    }
}
