<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class EmailAddresserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_email_addresser';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_email_email_address';
    }
}
