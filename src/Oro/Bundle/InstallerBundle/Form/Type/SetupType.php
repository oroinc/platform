<?php

namespace Oro\Bundle\InstallerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SetupType extends AbstractType
{
    protected $dataClass;

    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                TextType::class,
                array(
                    'label' => 'form.setup.username',
                )
            )
            ->add(
                'plainPassword',
                RepeatedType::class,
                array(
                    'type'           => 'password',
                    'invalid_message' => 'The password fields must match.',
                    'first_options'  => array('label' => 'form.setup.password'),
                    'second_options' => array('label' => 'form.setup.password_re'),
                )
            )
            ->add(
                'email',
                EmailType::class,
                array(
                    'label' => 'form.setup.email',
                )
            )
            ->add(
                'firstName',
                TextType::class,
                array(
                    'label' => 'form.setup.firstname',
                )
            )
            ->add(
                'lastName',
                TextType::class,
                array(
                    'label' => 'form.setup.lastname',
                )
            )
            ->add(
                'loadFixtures',
                CheckboxType::class,
                array(
                    'label'    => 'form.setup.load_fixtures',
                    'required' => false,
                    'mapped'   => false,
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'        => $this->dataClass,
                'validation_groups' => array('Registration', 'Default'),
            )
        );
    }

    public function getBlockPrefix()
    {
        return 'oro_installer_setup';
    }
}
