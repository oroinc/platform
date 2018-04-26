<?php

namespace Oro\Bundle\InstallerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SetupType extends AbstractType
{
    /** @var string */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'application_url',
                TextType::class,
                [
                    'label'    => 'form.setup.application_url',
                    'mapped'   => false,
                    'required' => false,
                ]
            )
            ->add(
                'organization_name',
                TextType::class,
                [
                    'label'       => 'form.setup.organization_name',
                    'mapped'      => false,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ]
            )
            ->add(
                'username',
                TextType::class,
                [
                    'label' => 'form.setup.username',
                ]
            )
            ->add(
                'plainPassword',
                RepeatedType::class,
                [
                    'type'            => PasswordType::class,
                    'invalid_message' => 'The password fields must match.',
                    'first_options'   => ['label' => 'form.setup.password'],
                    'second_options'  => ['label' => 'form.setup.password_re'],
                ]
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => 'form.setup.email',
                ]
            )
            ->add(
                'firstName',
                TextType::class,
                [
                    'label' => 'form.setup.firstname',
                ]
            )
            ->add(
                'lastName',
                TextType::class,
                [
                    'label' => 'form.setup.lastname',
                ]
            )
            ->add(
                'loadFixtures',
                CheckboxType::class,
                [
                    'label'    => 'form.setup.load_fixtures',
                    'required' => false,
                    'mapped'   => false,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'              => $this->dataClass,
                'validation_groups'       => ['Registration', 'Default'],
                'dynamic_fields_disabled' => true
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
        return 'oro_installer_setup';
    }
}
