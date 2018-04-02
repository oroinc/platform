<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationUserAclMultiSelectType extends UserMultiSelectType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'acl_users',
                'configs'            => [
                    'component'               => 'acl-user-multiselect',
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
        return OroJquerySelect2HiddenType::class;
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
        return 'oro_user_organization_acl_multiselect';
    }
}
