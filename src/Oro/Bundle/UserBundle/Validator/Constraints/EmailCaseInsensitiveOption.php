<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Disallows to enable case-insensitive email addresses if there are duplicates in emailLowercase column.
 * Disallows to disable case-insensitive email addresses if case insensitive collation is used for MySQL.
 */
class EmailCaseInsensitiveOption extends Constraint
{
    public string $collationMessage = 'oro.user.message.system_configuration.case_insensitive.collation';

    public string $duplicatedEmailsMessage =
        'oro.user.message.system_configuration.case_insensitive.duplicated_emails.message';

    public string $duplicatedEmailsClickHere =
        'oro.user.message.system_configuration.case_insensitive.duplicated_emails.click_here';
}
