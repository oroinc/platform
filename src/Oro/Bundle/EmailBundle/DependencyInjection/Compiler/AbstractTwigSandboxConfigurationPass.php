<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The base class to register Twig functions, filters and tags for the email templates rendering sandbox.
 */
abstract class AbstractTwigSandboxConfigurationPass implements CompilerPassInterface
{
    private const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    private const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerFunctions($container);
        $this->registerFilters($container);
        $this->registerTags($container);
        $this->registerExtensions($container);
    }

    /**
     * @return string[]
     */
    abstract protected function getFunctions(): array;

    /**
     * @return string[]
     */
    abstract protected function getFilters(): array;

    /**
     * @return string[]
     */
    abstract protected function getTags(): array;

    /**
     * @return string[]
     */
    abstract protected function getExtensions(): array;

    /**
     * Registers functions.
     */
    private function registerFunctions(ContainerBuilder $container): void
    {
        $functions = $this->getFunctions();
        if ($functions) {
            $this->addToSandboxSecurityPolicy($container, 4, $functions);
        }
    }

    /**
     * Registers filters.
     */
    private function registerFilters(ContainerBuilder $container): void
    {
        $filters = $this->getFilters();
        if ($filters) {
            $this->addToSandboxSecurityPolicy($container, 1, $filters);
        }
    }

    /**
     * Registers tags.
     */
    private function registerTags(ContainerBuilder $container): void
    {
        $tags = $this->getTags();
        if ($tags) {
            $this->addToSandboxSecurityPolicy($container, 0, $tags);
        }
    }

    /**
     * Registers extensions.
     */
    private function registerExtensions(ContainerBuilder $container): void
    {
        $rendererDef = $container->getDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY);
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            $rendererDef->addMethodCall('addExtension', [new Reference($extension)]);
        }
    }

    /**
     * Adds the given functions, filters or tags to the sandbox security policy.
     */
    private function addToSandboxSecurityPolicy(ContainerBuilder $container, int $argumentIndex, array $newItems): void
    {
        $securityPolicyDef = $container->getDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY);
        $securityPolicyDef->replaceArgument(
            $argumentIndex,
            array_merge($securityPolicyDef->getArgument($argumentIndex), $newItems)
        );
    }
}
