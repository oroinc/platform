<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EmailAddressRecipientsType extends AbstractType
{
    const NAME = 'oro_email_email_address_recipients';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'tooltip' => false,
            'configs' => [
                'allowClear'         => true,
                'multiple'           => true,
                'route_name'         => 'oro_api_get_email_recipient_autocomplete',
                'separator'          => ';',
                'minimumInputLength' => 1,
                'per_page'           => 100,
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
