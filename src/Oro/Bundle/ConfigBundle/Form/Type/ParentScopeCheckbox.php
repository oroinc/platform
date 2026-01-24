<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the "use parent scope value" checkbox in system configuration.
 *
 * Provides a specialized checkbox form type used in configuration forms to allow users
 * to inherit configuration values from parent scopes instead of defining scope-specific values.
 * When checked, this checkbox indicates that the configuration field should use the value
 * from the parent scope (e.g., global scope for organization-level settings). This enables
 * flexible configuration inheritance across different scope levels.
 */
class ParentScopeCheckbox extends AbstractType
{
    const NAME = 'oro_config_parent_scope_checkbox_type';

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CheckboxType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['required' => false, 'label' => 'oro.config.system_configuration.use_default']);
    }
}
