<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\UserBundle\Form\Type\UserAclSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationUserAclSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
        return UserAclSelectType::class;
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
        return 'oro_user_organization_acl_select';
    }
}
