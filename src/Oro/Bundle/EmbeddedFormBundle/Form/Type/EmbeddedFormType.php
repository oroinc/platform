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
            ->add('title', 'text')
            ->add('formType', 'oro_available_embedded_forms')
            ->add(
                'channel',
                'entity',
                [
                    'class' => 'OroIntegrationBundle:Channel',
                    'property' => 'name'
                ]
            )
            ->add('css', 'textarea')
            ->add('successMessage', 'textarea', ['tooltip' => 'oro.embeddedform.success_message.tooltip']);
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
