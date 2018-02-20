<?php

namespace Oro\Bundle\InstallerBundle\Form\Type;

use Oro\Bundle\InstallerBundle\Validator\Constraints as Assert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationType extends AbstractType
{
    const NAME = 'oro_installer_configuration';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'database',
                'oro_installer_configuration_database',
                [
                    'label'       => 'form.configuration.database.header',
                    'constraints' => [
                        new Assert\DatabaseConnection(),
                    ],
                ]
            )
            ->add(
                'mailer',
                'oro_installer_configuration_mailer',
                [
                    'label' => 'form.configuration.mailer.header'
                ]
            )
            ->add(
                'websocket',
                'oro_installer_configuration_websocket',
                [
                    'label' => 'form.configuration.websocket.header'
                ]
            )
            ->add(
                'system',
                'oro_installer_configuration_system',
                [
                    'label' => 'form.configuration.system.header'
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
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
        return self::NAME;
    }
}
