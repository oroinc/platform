<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for editing individual ACL permissions.
 *
 * This form type provides fields for configuring an ACL permission, including
 * the access level selector and the permission name. It is typically used as a
 * child form within permission collection forms.
 */
class AclPermissionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'accessLevel',
            $options['privileges_config']['field_type'],
            array(
                'required' => false,
            )
        );
        $builder->add(
            'name',
            HiddenType::class,
            array(
                'required' => false,
            )
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_acl_permission';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\SecurityBundle\Model\AclPermission',
                'privileges_config' => array()
            )
        );
    }
}
