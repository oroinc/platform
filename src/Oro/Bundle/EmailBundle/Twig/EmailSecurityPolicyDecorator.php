<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Decorates SecurityPolicy to override checkMethodAllowed to process entity classes.
 */
class EmailSecurityPolicyDecorator implements SecurityPolicyInterface
{
    private bool $initialized = false;

    public function __construct(
        private SecurityPolicyInterface $securityPolicy,
        private TemplateRendererConfigProviderInterface $templateRendererConfigProvider
    ) {
    }

    public function __call($name, $arguments)
    {
        return $this->securityPolicy->{$name}(...$arguments);
    }

    public function checkSecurity($tags, $filters, $functions): void
    {
        $this->ensureInitialized();

        $this->securityPolicy->checkSecurity($tags, $filters, $functions);
    }

    public function checkMethodAllowed($obj, $method): void
    {
        if (str_contains($obj::class, '\Entity\\')
            && (str_starts_with($method, 'get') || str_starts_with($method, 'is') || str_starts_with($method, 'has'))
        ) {
            return;
        }

        $this->ensureInitialized();

        $this->securityPolicy->checkMethodAllowed($obj, $method);
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        $this->ensureInitialized();

        $this->securityPolicy->checkPropertyAllowed($obj, $property);
    }

    private function ensureInitialized(): void
    {
        if (!$this->initialized) {
            $this->initialize();
        }
    }

    private function initialize(): void
    {
        $configuration = $this->templateRendererConfigProvider->getConfiguration();

        $methods = $this->addToStringMethod($configuration[TemplateRendererConfigProviderInterface::METHODS]);

        $this->securityPolicy->setAllowedMethods($methods);
        $this->securityPolicy
            ->setAllowedProperties($configuration[TemplateRendererConfigProviderInterface::PROPERTIES]);
    }

    private function addToStringMethod(array $configMethods): array
    {
        foreach ($configMethods as $className => &$methods) {
            $methods[] = '__toString';
        }

        return $configMethods;
    }
}
