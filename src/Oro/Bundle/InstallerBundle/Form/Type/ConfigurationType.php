<?php

namespace Oro\Bundle\InstallerBundle\Form\Type;

use Oro\Bundle\InstallerBundle\Validator\Constraints as Assert;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;

class ConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'database',
                'oro_installer_configuration_database',
                array(
                    'label'       => 'form.configuration.database.header',
                    'constraints' => array(
                        new Assert\DatabaseConnection(),
                    ),
                )
            )
            ->add(
                'mailer',
                'oro_installer_configuration_mailer',
                array(
                    'label' => 'form.configuration.mailer.header'
                )
            )
            ->add(
                'websocket',
                'oro_installer_configuration_websocket',
                array(
                    'label' => 'form.configuration.websocket.header'
                )
            )
            ->add(
                'system',
                'oro_installer_configuration_system',
                array(
                    'label' => 'form.configuration.system.header'
                )
            );
    }

    public function getBlockPrefix()
    {
        return 'oro_installer_configuration';
    }
}
