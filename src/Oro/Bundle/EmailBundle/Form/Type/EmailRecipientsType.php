<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class EmailRecipientsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_email_recipients';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_email_email_address';
    }
}
