<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class EnumValueType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', 'hidden')
            ->add(
                'label',
                'text',
                [
                    'required'    => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(['max' => 255])
                    ]
                ]
            )
            ->add(
                'is_default',
                $options['multiple'] ? 'checkbox' : 'radio',
                [
                    'required' => false
                ]
            )
            ->add(
                'priority',
                'hidden',
                [
                    'empty_data' => 9999
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'multiple' => false
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_enum_value';
    }
}
