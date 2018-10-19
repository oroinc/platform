<?php
namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;

use Oro\Bundle\EmbeddedFormBundle\Form\Type\AvailableEmbeddedFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmbeddedFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'embedded_form';
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm'
            ]
        );
    }
}
