<?php

namespace Oro\Bundle\ReminderBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReminderCollectionType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'entry_type'           => ReminderType::class,
                'required'             => false,
                'show_form_when_empty' => false,
                'error_bubbling'       => false,
                'entry_options'              => array(
                    'data_class' => 'Oro\\Bundle\\ReminderBundle\\Entity\\Reminder'
                )
            )
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_reminder_collection';
    }
}
