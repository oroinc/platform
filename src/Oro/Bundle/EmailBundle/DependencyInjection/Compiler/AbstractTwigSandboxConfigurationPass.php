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
    private const EMAIL_TEMPLATE_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_template_security_policy';

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
            // @bc-layer This calls is retained for BC reasons.
            $this->addToSandboxSecurityPolicy($container, 4, $functions);

            $this->addToSandboxSecurityPolicyCalls($container, 'setAllowedFunctions', $functions);
        }
    }

    /**
     * Registers filters.
     */
    private function registerFilters(ContainerBuilder $container): void
    {
        $filters = $this->getFilters();
        if ($filters) {
            // @bc-layer This calls is retained for BC reasons.
            $this->addToSandboxSecurityPolicy($container, 1, $filters);

            $this->addToSandboxSecurityPolicyCalls($container, 'setAllowedFilters', $filters);
        }
    }

    /**
     * Registers tags.
     */
    private function registerTags(ContainerBuilder $container): void
    {
        $tags = $this->getTags();
        if ($tags) {
            // @bc-layer This calls is retained for BC reasons.
            $this->addToSandboxSecurityPolicy($container, 0, $tags);

            $this->addToSandboxSecurityPolicyCalls($container, 'setAllowedTags', $tags);
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
     *
     * @bc-layer This method is retained for BC reasons.
     */
    private function addToSandboxSecurityPolicy(ContainerBuilder $container, int $argumentIndex, array $newItems): void
    {
        $securityPolicyDef = $container->getDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY);
        $securityPolicyDef->replaceArgument(
            $argumentIndex,
            array_merge($securityPolicyDef->getArgument($argumentIndex), $newItems)
        );
    }

    /**
     * Adds the given functions, filters or tags to the sandbox security policy.
     */
    private function addToSandboxSecurityPolicyCalls(
        ContainerBuilder $container,
        string $methodName,
        array $newItems
    ): void {
        $securityPolicyDef = $container->getDefinition(self::EMAIL_TEMPLATE_SECURITY_POLICY_SERVICE_KEY);
        $methodCalls = $securityPolicyDef->getMethodCalls();
        $found = false;
        foreach ($methodCalls as $i => $methodCall) {
            if ($methodCall[0] === $methodName) {
                $methodCalls[$i] = [
                    $methodCall[0],
                    [array_values(array_unique(array_merge($methodCall[1][0], $newItems)))]
                ];
                $found = true;
            }
        }

        if (!$found) {
            $methodCalls[] = [$methodName, [$newItems]];
        }

        $securityPolicyDef->setMethodCalls($methodCalls);
    }
}
