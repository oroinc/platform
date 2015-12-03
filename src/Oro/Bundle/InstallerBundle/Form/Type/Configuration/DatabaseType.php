<?php

namespace Oro\Bundle\InstallerBundle\Form\Type\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DatabaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'oro_installer_database_host',
                TextType::class,
                array(
                    'label'         => 'form.configuration.database.host',
                    'constraints'   => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'oro_installer_database_port',
                IntegerType::class,
                array(
                    'label'         => 'form.configuration.database.port',
                    'required'      => false,
                    'constraints'   => array(
                        new Assert\Type(array('type' => 'integer')),
                    ),
                )
            )
            ->add(
                'oro_installer_database_name',
                TextType::class,
                array(
                    'label'         => 'form.configuration.database.name',
                    'constraints'   => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'oro_installer_database_user',
                TextType::class,
                array(
                    'label'         => 'form.configuration.database.user',
                    'constraints'   => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'oro_installer_database_password',
                PasswordType::class,
                array(
                    'label'         => 'form.configuration.database.password',
                    'required'      => false,
                )
            );
    }

    public function getBlockPrefix()
    {
        return 'oro_installer_configuration_database';
    }
}
