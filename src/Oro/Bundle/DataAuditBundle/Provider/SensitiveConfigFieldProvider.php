<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormRegistryInterface;

/**
 * Tells whether a configuration setting holds a secret — a password, an API key, a token.
 *
 * The setting is recognised by the form type it is edited with: everything rendered as a password field
 * hides its value from the user in the System Configuration form, so the audit must not disclose it either.
 * That covers Symfony's {@see PasswordType} and every Oro type built on top of it
 * (OroPlaceholderPasswordType, OroEncodedPlaceholderPasswordType, ...), and it needs no list to maintain:
 * a bundle that adds a secret setting is covered as soon as it renders it as a password.
 */
class SensitiveConfigFieldProvider
{
    /** [configuration key => is sensitive] */
    private array $sensitive = [];

    public function __construct(
        private readonly ConfigBag $configBag,
        private readonly FormRegistryInterface $formRegistry
    ) {
    }

    public function isSensitive(string $configKey): bool
    {
        return $this->sensitive[$configKey] ??= $this->isPasswordField($configKey);
    }

    private function isPasswordField(string $configKey): bool
    {
        $field = $this->configBag->getFieldsRoot($configKey);
        $formType = \is_array($field) ? ($field['type'] ?? null) : null;
        if (!$formType) {
            return false;
        }

        try {
            $resolvedType = $this->formRegistry->getType($formType);
        } catch (\Throwable) {
            // A setting whose form type is not usable cannot be edited, hence cannot be changed here. It
            // must not turn the configuration save that is already committed into an error either.
            return false;
        }

        while (null !== $resolvedType) {
            if ($resolvedType->getInnerType() instanceof PasswordType) {
                return true;
            }
            $resolvedType = $resolvedType->getParent();
        }

        return false;
    }
}
