<?php
namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EmbeddedFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'embedded_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', ['label' => 'oro.embeddedform.title.label'])
            ->add('formType', 'oro_available_embedded_forms', ['label' => 'oro.embeddedform.form_type.label'])
            ->add(
                'css',
                'textarea',
                [
                    'label'   => 'oro.embeddedform.css.label',
                    'tooltip' => 'oro.embeddedform.css.description'
                ]
            )
            ->add(
                'successMessage',
                'textarea',
                [
                    'label'   => 'oro.embeddedform.success_message.label',
                    'tooltip' => 'oro.embeddedform.success_message.description'
                ]
            )
            ->add(
                'allowedDomains',
                'textarea',
                [
                    'label'   => 'oro.embeddedform.allowed_domains.label',
                    'required' => false,
                    'tooltip' => 'oro.embeddedform.allowed_domains.description'
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm'
            ]
        );
    }
}
