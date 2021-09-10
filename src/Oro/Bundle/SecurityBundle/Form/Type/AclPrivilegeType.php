<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that provides inputs for ACL identity and permissions.
 */
class AclPrivilegeType extends AbstractType
{
    const NAME = 'oro_acl_privilege';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'identity',
            AclPrivilegeIdentityType::class,
            array(
                'required' => false,
            )
        );

        $builder->add(
            'permissions',
            PermissionCollectionType::class,
            array(
                'entry_type' => new AclPermissionType(),
                'allow_add' => true,
                'prototype' => false,
                'allow_delete' => false,
                'entry_options' => array(
                    'privileges_config' => $options['privileges_config']
                ),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['privileges_config'] = $options['privileges_config'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'privileges_config' => array(),
                'data_class' => AclPrivilege::class,
            )
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
        return self::NAME;
    }
}
