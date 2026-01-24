<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

/**
 * Workflow action for extracting last name from an email address.
 *
 * Parses an email address to extract the last name portion and stores it in a workflow context variable,
 * useful for pre-populating contact information in automated workflows.
 */
class ParseLastNameFromEmailAddress extends AbstractParseEmailAddressAction
{
    #[\Override]
    protected function executeAction($context)
    {
        $address = $this->contextAccessor->getValue($context, $this->address);

        $name = $this->addressHelper->extractEmailAddressLastName($address);

        $this->contextAccessor->setValue($context, $this->attribute, $name);
    }
}
