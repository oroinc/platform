<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\Form\Type\AclPrivilegeType;
use Oro\Bundle\SecurityBundle\Form\Type\PrivilegeCollectionType;

class AclRoleType extends AbstractType
{
    /**
     * @var array privilege fields config
     */
    protected $privilegeConfig;

    /**
     * @param array $privilegeTypeConfig
     */
    public function __construct(array $privilegeTypeConfig = array())
    {
        $this->privilegeConfig = $privilegeTypeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'label',
            'text',
            array(
                'required' => true,
                'label' => 'oro.user.role.role.label'
            )
        );

        foreach ($this->privilegeConfig as $fieldName => $config) {
            $builder->add(
                $fieldName,
                PrivilegeCollectionType::NAME,
                [
                    'type' => AclPrivilegeType::NAME,
                    'allow_add' => true,
                    'prototype' => false,
                    'allow_delete' => false,
                    'mapped' => false,
                    'options' => [
                        'privileges_config' => $config,
                    ]
                ]
            );
        }

        $builder->add(
            'appendUsers',
            'oro_entity_identifier',
            array(
                'class'    => 'OroUserBundle:User',
                'required' => false,
                'mapped'   => false,
                'multiple' => true,
            )
        );

        $builder->add(
            'removeUsers',
            'oro_entity_identifier',
            array(
                'class'    => 'OroUserBundle:User',
                'required' => false,
                'mapped'   => false,
                'multiple' => true,
            )
        );
        $builder->add(
            'privileges',
            'hidden',
            [
                'mapped' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\UserBundle\Entity\Role',
                'intention'  => 'role',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_user_role_form';
    }
}
