<?php

namespace Oro\Bundle\FormBundle\Utils;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Provides a set of static methods that may be helpful for working with CSRF token.
 */
class CsrfTokenUtils
{
    public static function isCsrfProtectionEnabled(FormInterface $form): bool
    {
        return $form->getConfig()->getOption('csrf_protection');
    }

    public static function getCsrfToken(FormInterface $form): ?CsrfToken
    {
        $formConfig = $form->getConfig();
        if (!self::isCsrfProtectionEnabled($form)) {
            return null;
        }

        $csrfTokenId = $formConfig->getOption('csrf_token_id')
            ?: $form->getName()
                ?: \get_class($formConfig->getType()->getInnerType());

        /** @var CsrfTokenManagerInterface $csrfTokenManager */
        $csrfTokenManager = $formConfig->getOption('csrf_token_manager');

        return $csrfTokenManager->getToken($csrfTokenId);
    }

    public static function getCsrfFieldName(FormInterface $form): ?string
    {
        $formConfig = $form->getConfig();

        if (!self::isCsrfProtectionEnabled($form)) {
            return null;
        }

        return $formConfig->getOption('csrf_field_name');
    }
}
