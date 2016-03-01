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

        $name = $this->addressHelper->extractEmailAddressLastName($address);

        $this->contextAccessor->setValue($context, $this->attribute, $name);
    }
}
