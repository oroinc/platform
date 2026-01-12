<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

/**
 * Filters request parameters by extracting pure email addresses.
 *
 * Uses the {@see EmailAddressHelper} to extract email addresses from parameter values,
 * handling both single values and arrays of values. Removes any display names or
 * other formatting to return only the email address portion.
 */
class EmailAddressParameterFilter implements ParameterFilterInterface
{
    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    public function __construct(EmailAddressHelper $emailAddressHelper)
    {
        $this->emailAddressHelper = $emailAddressHelper;
    }

    #[\Override]
    public function filter($rawValue, $operator)
    {
        if (is_array($rawValue)) {
            return array_map(
                function ($val) {
                    return $this->emailAddressHelper->extractPureEmailAddress($val);
                },
                $rawValue
            );
        } else {
            return $this->emailAddressHelper->extractPureEmailAddress($rawValue);
        }
    }
}
