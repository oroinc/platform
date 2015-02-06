<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OrganizationUserAclMultiSelectType extends UserMultiSelectType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'acl_users',
                'configs'            => [
                    'extra_config'            => 'acl_users_multiselect',
                    'permission'              => 'VIEW',
                    'entity_name'             => 'Oro\Bundle\UserBundle\Entity\User',
                    'entity_id'               => 0,
                    'multiple'                => true,
                    'width'                   => '400px',
                    'placeholder'             => 'oro.user.form.choose_user',
                    'allowClear'              => true,
                    'result_template_twig'    => 'OroUserBundle:User:Autocomplete/result.html.twig',
                    'selection_template_twig' => 'OroUserBundle:User:Autocomplete/selection.html.twig',
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_user_organization_acl_multiselect';
    }
}
