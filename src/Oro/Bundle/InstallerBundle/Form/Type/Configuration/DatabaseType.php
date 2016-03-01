<?php

namespace Oro\Bundle\InstallerBundle\Form\Type\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\InstallerBundle\Validator\Constraints\ExtensionLoaded;

class DatabaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'oro_installer_database_driver',
                'choice',
                array(
                    'label'       => 'form.configuration.database.driver',
                    'choices'       => array(
                        DatabaseDriverInterface::DRIVER_MYSQL      => 'MySQL',
                        DatabaseDriverInterface::DRIVER_POSTGRESQL => 'PostgreSQL',
                    ),
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new ExtensionLoaded(),
                    ),
                )
            )
            ->add(
                'oro_installer_database_host',
                'text',
                array(
                    'label'       => 'form.configuration.database.host',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'oro_installer_database_port',
                'integer',
                array(
                    'label'       => 'form.configuration.database.port',
                    'required'    => false,
                    'constraints' => array(
                        new Assert\Type(array('type' => 'integer')),
                    ),
                )
            )
            ->add(
                'oro_installer_database_name',
                'text',
                array(
                    'label'       => 'form.configuration.database.name',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'oro_installer_database_user',
                'text',
                array(
                    'label'       => 'form.configuration.database.user',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'oro_installer_database_password',
                'password',
                array(
                    'label'    => 'form.configuration.database.password',
                    'required' => false,
                )
            )
            ->add(
                'oro_installer_database_drop',
                'choice',
                array(
                    'label'         => 'form.configuration.database.drop',
                    'data'          => 'none',
                    'choices'       => array(
                        'none' => 'form.configuration.database.drop_none',
                        'app'  => 'form.configuration.database.drop_app',
                        'full' => 'form.configuration.database.drop_full'
                    ),
                    'constraints'   => array(
                        new Assert\NotBlank()
                    ),
                )
            );
    }

    public function getName()
    {
        return 'oro_installer_configuration_database';
    }
}
