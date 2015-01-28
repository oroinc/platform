<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CheckButtonType extends ButtonType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_imap_configuration_check';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'button';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(['attr' => ['class' => 'btn btn-primary']]);
    }
}
