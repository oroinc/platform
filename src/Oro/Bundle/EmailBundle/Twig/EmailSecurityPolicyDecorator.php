<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Decorates SecurityPolicy to override checkMethodAllowed to process entity classes.
 */
class EmailSecurityPolicyDecorator implements SecurityPolicyInterface
{
    public function __construct(
        private SecurityPolicyInterface $securityPolicy
    ) {
    }

    public function __call($name, $arguments)
    {
        return $this->securityPolicy->{$name}(...$arguments);
    }

    public function checkSecurity($tags, $filters, $functions): void
    {
        $this->securityPolicy->checkSecurity($tags, $filters, $functions);
    }

    public function checkMethodAllowed($obj, $method): void
    {
        if (str_contains($obj::class, '\Entity\\')
            && (str_starts_with($method, 'get') || str_starts_with($method, 'is') || str_starts_with($method, 'has'))
        ) {
            return;
        }
        $this->securityPolicy->checkMethodAllowed($obj, $method);
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        $this->securityPolicy->checkPropertyAllowed($obj, $property);
    }
}
