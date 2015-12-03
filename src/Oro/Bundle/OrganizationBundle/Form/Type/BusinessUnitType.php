<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessUnitType extends AbstractType
{
    const FORM_NAME = 'oro_business_unit';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                array(
                    'required' => true,
                )
            )
            ->add(
                'phone',
                TextType::class,
                array(
                    'required' => false,
                )
            )
            ->add(
                'website',
                TextType::class,
                array(
                    'required' => false,
                )
            )
            ->add(
                'email',
                TextType::class,
                array(
                    'required' => false,
                )
            )
            ->add(
                'fax',
                TextType::class,
                array(
                    'required' => false,
                )
            )
            ->add(
                'organization',
                'entity',
                array(
                    'label'    => 'Organization',
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit'
            )
        );
    }

    public function getBlockPrefix()
    {
        return self::FORM_NAME;
    }
}
