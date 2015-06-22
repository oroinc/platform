<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OrganizationUserAclSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'configs'            => [
                    'placeholder'             => 'oro.user.form.choose_user',
                    'result_template_twig'    => 'OroUserBundle:User:Autocomplete/result.html.twig',
                    'selection_template_twig' => 'OroUserBundle:User:Autocomplete/selection.html.twig',
                    'component'               => 'acl-user-autocomplete',
                    'permission'              => 'VIEW',
                    'entity_name'             => 'Oro\Bundle\UserBundle\Entity\User',
                    'entity_id'               => 0
                ],
                'autocomplete_alias' => 'acl_users',
                'grid_name'          => 'owner-users-select-grid',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_user_acl_select';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_user_organization_acl_select';
    }
}
