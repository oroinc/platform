<?php

namespace Oro\Bundle\EmailBundle\Model;

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
