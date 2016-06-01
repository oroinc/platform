<?php

namespace Oro\Bundle\ReminderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ReminderCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'type'                 => 'oro_reminder',
                'required'             => false,
                'show_form_when_empty' => false,
                'error_bubbling'       => false,
                'options'              => array(
                    'data_class' => 'Oro\\Bundle\\ReminderBundle\\Entity\\Reminder'
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_reminder_collection';
    }
}
