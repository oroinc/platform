<?php

namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Embedded form type
 */
class EmbeddedFormType extends AbstractType
{
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'embedded_form';
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => 'oro.embeddedform.title.label'])
            ->add('formType', AvailableEmbeddedFormType::class, ['label' => 'oro.embeddedform.form_type.label'])
            ->add(
                'css',
                TextareaType::class,
                [
                    'label'   => 'oro.embeddedform.css.label',
                    'tooltip' => 'oro.embeddedform.css.description'
                ]
            )
            ->add(
                'successMessage',
                TextareaType::class,
                [
                    'label'   => 'oro.embeddedform.success_message.label',
                    'tooltip' => 'oro.embeddedform.success_message.description'
                ]
            )
            ->add(
                'allowedDomains',
                TextareaType::class,
                [
                    'label'   => 'oro.embeddedform.allowed_domains.label',
                    'required' => false,
                    'tooltip' => 'oro.embeddedform.allowed_domains.description'
                ]
            )
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm'
            ]
        );
    }
}
