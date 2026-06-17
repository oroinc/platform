<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Decorates SecurityPolicy to override checkMethodAllowed to process entity classes.
 *
 * @bc-layer This class is retained for BC reasons, use {@see EmailTemplateSecurityPolicy} instead.
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
        // __toString is a PHP string-coercion magic method, not an entity field accessor.
        if (strtolower($method) === '__tostring') {
            return;
        }

        $this->securityPolicy->checkMethodAllowed($obj, $method);
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        $this->securityPolicy->checkPropertyAllowed($obj, $property);
    }
}
