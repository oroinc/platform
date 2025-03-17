<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type provides functionality to select an existing entity from the tree
 */
class OrganizationUserAclMultiSelectType extends UserMultiSelectType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
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
                    'placeholder'             => 'oro.user.form.choose_user',
                    'allowClear'              => true,
                    'result_template_twig'    => '@OroUser/User/Autocomplete/result.html.twig',
                    'selection_template_twig' => '@OroUser/User/Autocomplete/selection.html.twig',
                ]
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroJquerySelect2HiddenType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_user_organization_acl_multiselect';
    }
}
