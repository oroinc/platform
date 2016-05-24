<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPass implements CompilerPassInterface
{
    const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY                = 'oro_email.email_renderer';
    const CONFIG_EXTENSION_SERVICE_KEY                       = 'oro_config.twig.config_extension';

    const FORMATTER_EXTENSION_SERVICE_KEY = 'oro_ui.twig.extension.formatter';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            && $container->hasDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY)
        ) {
            // register 'oro_config_value' function
            $securityPolicyDef = $container->getDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY);
            $rendererDef       = $container->getDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY);

            if ($container->hasDefinition(self::CONFIG_EXTENSION_SERVICE_KEY)) {
                $functions = $securityPolicyDef->getArgument(4);
                $functions = array_merge($functions, ['oro_config_value']);
                $securityPolicyDef->replaceArgument(4, $functions);
                // register an twig extension implements this function
                $rendererDef->addMethodCall('addExtension', [new Reference(self::CONFIG_EXTENSION_SERVICE_KEY)]);
            }

            if ($container->hasDefinition(self::FORMATTER_EXTENSION_SERVICE_KEY)) {
                $filters = $securityPolicyDef->getArgument(1);
                $filters = array_merge($filters, ['oro_format']);
                $securityPolicyDef->replaceArgument(1, $filters);
                // register an twig extension implements this function
                $rendererDef->addMethodCall('addExtension', [new Reference(self::FORMATTER_EXTENSION_SERVICE_KEY)]);
            }
        }
    }
}