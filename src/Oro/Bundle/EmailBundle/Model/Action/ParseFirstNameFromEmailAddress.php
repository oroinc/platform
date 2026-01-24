<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

/**
 * Workflow action for extracting first name from an email address.
 *
 * Parses an email address to extract the first name portion and stores it in a workflow context variable,
 * useful for pre-populating contact information in automated workflows.
 */
class ParseFirstNameFromEmailAddress extends AbstractParseEmailAddressAction
{
    #[\Override]
    protected function executeAction($context)
    {
        $address = $this->contextAccessor->getValue($context, $this->address);

        $name = $this->addressHelper->extractEmailAddressFirstName($address);

        $this->contextAccessor->setValue($context, $this->attribute, $name);
    }
}
