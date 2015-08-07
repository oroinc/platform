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

        $name = $this->addressHelper->extractEmailAddressFirstName($address);

        $this->contextAccessor->setValue($context, $this->attribute, $name);
    }
}
