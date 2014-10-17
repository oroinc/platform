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
            ->add('css', 'textarea', ['tooltip' => 'oro.embeddedform.css.tooltip', 'label' => 'oro.embeddedform.css.label'])
            ->add('successMessage', 'textarea', ['tooltip' => 'oro.embeddedform.success_message.tooltip', 'label' => 'oro.embeddedform.success_message.label']);
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
