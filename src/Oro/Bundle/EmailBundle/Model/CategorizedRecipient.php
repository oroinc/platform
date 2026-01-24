<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Represents a recipient with organization categorization.
 *
 * Extends the base recipient class to include organization information in the recipient label,
 * providing better context for recipients from different organizations.
 */
class CategorizedRecipient extends Recipient
{
    #[\Override]
    public function getName()
    {
        return $this->name;
    }

    #[\Override]
    public function getLabel()
    {
        if (!$this->entity || !$this->entity->getOrganization()) {
            return $this->getName();
        }

        return sprintf('%s (%s)', $this->getName(), $this->entity->getOrganization());
    }
}
