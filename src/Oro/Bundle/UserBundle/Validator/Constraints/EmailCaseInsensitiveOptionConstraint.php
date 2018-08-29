<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Disallows to enable case-insensitive email addresses if there are duplicates in emailLowercase column.
 * Disallows to disable case-insensitive email addresses if case insensitive collation is used for MySQL.
 */
class EmailCaseInsensitiveOptionConstraint extends Constraint
{
    /** @var string */
    public $collationMessage = 'oro.user.message.system_configuration.case_insensitive.collation';

    /** @var string */
    public $duplicatedEmailsMessage =
        'oro.user.message.system_configuration.case_insensitive.duplicated_emails.message';

    /** @var string */
    public $duplicatedEmailsClickHere =
        'oro.user.message.system_configuration.case_insensitive.duplicated_emails.click_here';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_user.validator.email_case_insensitive_option';
    }
}
