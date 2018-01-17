<?php

namespace Oro\Bundle\InstallerBundle\Form\Type\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DriverOptionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'option_key',
                TextType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'form.configuration.database.driver_options.option_key.label',
                    ],
                    'constraints' => [new Assert\NotBlank()]
                ]
            )
            ->add(
                'option_value',
                TextType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'form.configuration.database.driver_options.option_value.label',
                    ],
                    'constraints' => [new Assert\NotBlank()]
                ]
            );
    }

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
        return 'oro_installer_configuration_driver_option';
    }
}
