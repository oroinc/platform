<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Form type for defining a single unique key constraint.
 *
 * This form type represents a single unique key definition with a name and a collection
 * of field names that comprise the key. It provides two form fields: a text field for the
 * unique key name and a choice field for selecting one or more fields that should be part
 * of the unique constraint.
 */
class UniqueKeyType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            array(
                'label' => 'oro.entity_extend.form.name.label',
                'required' => true,
                'constraints' => [new Assert\NotBlank()]
            )
        );

        $builder->add(
            'key',
            ChoiceType::class,
            array(
                'label' => 'oro.entity_extend.form.key.label',
                'multiple' => true,
                'choices'  => $options['key_choices'],
                'required' => true,
                'constraints' => [new Assert\NotBlank()]
            )
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('key_choices');
        $resolver->setAllowedTypes('key_choices', 'array');
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_extend_unique_key_type';
    }
}
