<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\Cache\TemplateCacheCacheWarmer;
use Oro\Bundle\UIBundle\Twig\Environment;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that collect extensions for service `oro_ui.twig.html_tag` by `oro_email.email_renderer` tag.
 */
class TwigSandboxConfigurationPass implements CompilerPassInterface
{
    const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';
    const UI_EXTENSION_SERVICE_KEY = 'oro_ui.twig.html_tag';
    const TWIG_SERVICE_ID = 'twig';
    const TWIG_CACHE_WARMER_SERVICE_ID = 'twig.cache_warmer';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            && $container->hasDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY)
            && $container->hasDefinition(self::UI_EXTENSION_SERVICE_KEY)
        ) {
            // register 'oro_html_strip_tags' filter
            $securityPolicyDef = $container->getDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY);
            $filters = $securityPolicyDef->getArgument(1);
            $filters = array_merge($filters, ['oro_html_strip_tags']);
            $securityPolicyDef->replaceArgument(1, $filters);
            // register an twig extension implements this function
            $rendererDef = $container->getDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY);
            $rendererDef->addMethodCall('addExtension', [new Reference(self::UI_EXTENSION_SERVICE_KEY)]);
        }

        if ($container->hasDefinition(self::TWIG_SERVICE_ID)) {
            $container->getDefinition(self::TWIG_SERVICE_ID)->setClass(Environment::class);
        }

        if ($container->hasDefinition(self::TWIG_CACHE_WARMER_SERVICE_ID)) {
            $container->getDefinition(self::TWIG_CACHE_WARMER_SERVICE_ID)->setClass(TemplateCacheCacheWarmer::class);
        }
    }
}
