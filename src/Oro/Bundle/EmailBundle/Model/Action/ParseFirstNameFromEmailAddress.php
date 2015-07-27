<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

class ParseFirstNameFromEmailAddress extends AbstractParseEmailAddressAction
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
            // Use first part of name as first name
            $name = $name[0];
        } else {
            $name = $this->addressHelper->extractPureEmailAddress($address);
            $name = explode('@', $name);

            if (count($name) == 2) {
                // use account name as first name
                $name = $name[0];
            } else {
                // Give up (probably not a valid email address)
                $name = null;
            }
        }

        $this->contextAccessor->setValue($context, $this->attribute, $name);
    }
}
