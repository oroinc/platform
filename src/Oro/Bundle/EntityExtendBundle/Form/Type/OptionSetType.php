<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OptionSetType extends AbstractType
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

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            array($this, 'postSubmit')
        );
    }

    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (is_null($data->getLabel())) {
            $event->getForm()->get('label')->addError(
                new FormError('This value should not be blank.')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\EntityConfigBundle\Entity\OptionSet',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_option_set';
    }
}
