<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrganizationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'enabled',
                'choice',
                [
                    'required' => true,
                    'label' => 'oro.organization.enabled.label',
                    'choices' => [1 => 'Active', 0 => 'Inactive']
                ]
            )
            ->add(
                'name',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.organization.name.label',
                    'constraints' => [
                        new NotBlank()
                    ]
                ]
            )
            ->add(
                'description',
                'textarea',
                [
                    'required' => false,
                    'label' => 'oro.organization.description.label'
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'           => 'Oro\Bundle\OrganizationBundle\Entity\Organization',
                'intention'            => 'organization',
                'cascade_validation'   => true,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_organization';
    }
}
