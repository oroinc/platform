<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ShareType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('entityClass', 'hidden', ['required' => false])
            ->add('entityId', 'hidden', ['required' => false])
            ->add(
                'users',
                'entity',
                [
                    'class'    => 'OroUserBundle:User',
                    'property' => 'username',
                    'label'    => 'oro.user.entity_plural_label',
                    'required' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'businessunits',
                'entity',
                [
                    'class'    => 'OroOrganizationBundle:BusinessUnit',
                    'property' => 'name',
                    'label'    => 'oro.organization.businessunit.entity_plural_label',
                    'required' => false,
                    'multiple' => true,
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
                'data_class'         => 'Oro\Bundle\SecurityBundle\Form\Model\Share',
                'intention'          => 'email',
                'csrf_protection'    => true,
                'cascade_validation' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_share';
    }
}
