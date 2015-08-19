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
}
