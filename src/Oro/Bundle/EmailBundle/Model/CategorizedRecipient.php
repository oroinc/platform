<?php

namespace Oro\Bundle\EmailBundle\Model;

class CategorizedRecipient extends Recipient
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        if (!$this->entity || !$this->entity->getOrganization()) {
            return $this->getName();
        }

        return sprintf('%s (%s)', $this->getName(), $this->entity->getOrganization());
    }
}
