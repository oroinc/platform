<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

class ParseLastNameFromEmailAddress extends AbstractParseEmailAddressAction
{
    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $address = $this->contextAccessor->getValue($context, $this->address);

        $name = $this->addressHelper->extractEmailAddressName($address);

        if ($name !== null) {
            $name = explode(' ', $name);
            if (count($name) == 1) {
                // use first and only name as last name
                $name = $name[0];
            } else {
                // remove first name and use rest as last name
                unset($name[0]);
                $name = implode(' ', $name);
            }
        } else {
            $name = $this->addressHelper->extractPureEmailAddress($address);
            $name = explode('@', $name);

            if (count($name) == 2) {
                // use domain name as last name
                $name = $name[1];
            } else {
                // Give up (probably not a valid email address)
                $name = null;
            }
        }

        $this->contextAccessor->setValue($context, $this->attribute, $name);
    }
}
