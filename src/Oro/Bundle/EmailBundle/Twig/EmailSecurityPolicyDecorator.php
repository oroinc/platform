<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Decorates SecurityPolicy to override checkMethodAllowed to process entity classes.
 *
 * @bc-layer This class is retained for BC reasons, use {@see EmailTemplateSecurityPolicy} instead.
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
        // __toString is a PHP string-coercion magic method, not an entity field accessor.
        if (strtolower($method) === '__tostring') {
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
        $this->initialized = true;

        $configuration = $this->templateRendererConfigProvider->getConfiguration();

        $this->securityPolicy->setAllowedMethods($configuration[TemplateRendererConfigProviderInterface::METHODS]);
        $this->securityPolicy
            ->setAllowedProperties($configuration[TemplateRendererConfigProviderInterface::PROPERTIES]);
    }
}
