<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EnumType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', 'hidden')
            ->add(
                'priority',
                'hidden',
                [
                    'empty_data' => 9999
                ]
            )
            ->add(
                'label',
                'text',
                [
                    'label' => 'Value',
                    'required' => true,
                ]
            )
            ->add(
                'is_default',
                'radio',
                [
                    'label' => 'Default',
                    'required' => false,
                ]
            );

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * Validate label for each option on post submit
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (empty($data['label'])) {
            $event->getForm()->get('label')->addError(
                new FormError('This value should not be blank.')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_enum_value';
    }
}
