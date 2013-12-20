<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\ORM\EntityRepository;

class BusinessUnitType extends AbstractType
{
    const FORM_NAME = 'oro_business_unit';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                'text',
                array(
                    'label'    => 'oro.organization.businessunit.name.label',
                    'required' => true,
                )
            )
            ->add(
                'phone',
                'text',
                array(
                    'label'    => 'oro.organization.businessunit.phone.label',
                    'required' => false,
                )
            )
            ->add(
                'website',
                'text',
                array(
                    'label'    => 'oro.organization.businessunit.website',
                    'required' => false,
                )
            )
            ->add(
                'email',
                'text',
                array(
                    'label'    => 'oro.organization.businessunit.email.label',
                    'required' => false,
                )
            )
            ->add(
                'fax',
                'text',
                array(
                    'label'    => 'oro.organization.businessunit.fax.label',
                    'required' => false,
                )
            )
            ->add(
                'organization',
                'entity',
                array(
                    'label'    => 'oro.organization.businessunit.organization.label',
                    'class'    => 'OroOrganizationBundle:Organization',
                    'property' => 'name',
                    'required' => true,
                    'multiple' => false,
                )
            )
            ->add(
                'appendUsers',
                'oro_entity_identifier',
                array(
                    'class'    => 'OroUserBundle:User',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                )
            )
            ->add(
                'removeUsers',
                'oro_entity_identifier',
                array(
                    'class'    => 'OroUserBundle:User',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                )
            );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit'
            )
        );
    }

    public function getName()
    {
        return self::FORM_NAME;
    }
}
