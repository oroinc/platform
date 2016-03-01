<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

class EmailAddressParameterFilter implements ParameterFilterInterface
{
    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /**
     * @param EmailAddressHelper $emailAddressHelper
     */
    public function __construct(EmailAddressHelper $emailAddressHelper)
    {
        $this->emailAddressHelper = $emailAddressHelper;
    }

    /**
     * {@inheritdoc}
     */
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
