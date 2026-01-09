<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides a form type for selecting organizations using jQuery Select2 autocomplete.
 *
 * This form type renders as a hidden input field with autocomplete functionality, allowing
 * users to search and select organizations from a list. It uses the `user_organizations`
 * autocomplete alias to populate available options.
 */
class OrganizationSelectType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'configs' => [
                    'placeholder' => 'oro.organization.form.choose_organization',
                ],
                'autocomplete_alias' => 'user_organizations'
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroJquerySelect2HiddenType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_organization_select';
    }
}
