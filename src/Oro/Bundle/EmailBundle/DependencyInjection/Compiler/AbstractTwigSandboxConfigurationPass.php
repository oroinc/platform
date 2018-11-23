<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that collects extensions for services by `oro_email.email_renderer` tag
 */
abstract class AbstractTwigSandboxConfigurationPass implements CompilerPassInterface
{
    const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerFunctions($container);
        $this->registerFilters($container);
        $this->registerTwigExtensions($container);
    }

    /**
     * Register functions
     *
     * @param ContainerBuilder $container
     */
    private function registerFunctions(ContainerBuilder $container)
    {
        $functions = $this->getFunctions();
        if ($functions) {
            $this->registerArgument($container, 4, $functions);
        }
    }

    /**
     * Register filters
     *
     * @param ContainerBuilder $container
     */
    private function registerFilters(ContainerBuilder $container)
    {
        $filters = $this->getFilters();
        if ($filters) {
            $this->registerArgument($container, 1, $filters);
        }
    }

    /**
     * Register a specific argument
     *
     * @param ContainerBuilder $container
     * @param int $argumentIndex
     * @param array $argument
     */
    private function registerArgument(ContainerBuilder $container, $argumentIndex, $argument)
    {
        $securityPolicyDef = $container->getDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY);
        $argument = array_merge(
            $securityPolicyDef->getArgument($argumentIndex),
            $argument
        );
        $securityPolicyDef->replaceArgument($argumentIndex, $argument);
    }

    /**
     * Register a twig extensions
     *
     * @param ContainerBuilder $container
     */
    private function registerTwigExtensions(ContainerBuilder $container)
    {
        $rendererDef = $container->getDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY);
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            $rendererDef->addMethodCall('addExtension', [new Reference($extension)]);
        }
    }

    /**
     * @return array
     */
    abstract protected function getFunctions();

    /**
     * @return array
     */
    abstract protected function getExtensions();

    /**
     * @return array
     */
    abstract protected function getFilters();
}
