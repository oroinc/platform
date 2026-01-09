<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * API form type for individual tag entities.
 *
 * This form type provides API-specific form configuration for tag entities, including a required name field
 * with validation constraints. It is designed to be used within tag collections in API contexts and
 * disables error bubbling to provide granular validation feedback.
 */
class TagEntityApiType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label'       => 'oro.tag.name.label',
                'required'    => true,
                'constraints' => [new Assert\NotBlank()]
            ]
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'error_bubbling'       => false,
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_tag_tag_api';
    }
}
