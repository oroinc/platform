<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
                // TODO: replace with real class or remove
                'data_class' => 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_enum_value';
    }
}
