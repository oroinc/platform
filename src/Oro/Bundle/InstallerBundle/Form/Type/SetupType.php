<?php

namespace Oro\Bundle\InstallerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
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
                'text',
                [
                    'label'    => 'form.setup.application_url',
                    'mapped'   => false,
                    'required' => false,
                ]
            )
            ->add(
                'organization_name',
                'text',
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
                'text',
                [
                    'label' => 'form.setup.username',
                ]
            )
            ->add(
                'plainPassword',
                'repeated',
                [
                    'type'            => 'password',
                    'invalid_message' => 'The password fields must match.',
                    'first_options'   => ['label' => 'form.setup.password'],
                    'second_options'  => ['label' => 'form.setup.password_re'],
                ]
            )
            ->add(
                'email',
                'email',
                [
                    'label' => 'form.setup.email',
                ]
            )
            ->add(
                'firstName',
                'text',
                [
                    'label' => 'form.setup.firstname',
                ]
            )
            ->add(
                'lastName',
                'text',
                [
                    'label' => 'form.setup.lastname',
                ]
            )
            ->add(
                'loadFixtures',
                'checkbox',
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
