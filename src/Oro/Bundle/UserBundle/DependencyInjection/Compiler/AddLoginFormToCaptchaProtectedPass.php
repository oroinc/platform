<?php

namespace Oro\Bundle\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds User login form to a list of CAPTCHA protected forms.
 *
 * "oro_user_form_login is" a "fake" form name used to identify form later during checks and allow it to be
 * configured via the system configuration.
 */
class AddLoginFormToCaptchaProtectedPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('oro_form.captcha.protected_forms_registry')
            ->addMethodCall('protectForm', ['oro_user_form_login']);
    }
}
