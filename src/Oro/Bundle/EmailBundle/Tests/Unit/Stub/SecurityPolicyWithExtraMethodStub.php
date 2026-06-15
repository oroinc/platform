<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

use Twig\Sandbox\SecurityPolicyInterface;

/**
 * A stub implementing SecurityPolicyInterface with an extra method not defined in the interface,
 * used to verify that EmailTemplateSecurityPolicy::__call delegates unknown method calls to the
 * inner security policy.
 */
class SecurityPolicyWithExtraMethodStub implements SecurityPolicyInterface
{
    public function extraMethod(string $arg): string
    {
        return 'stub_result_' . $arg;
    }

    #[\Override]
    public function checkSecurity($tags, $filters, $functions): void
    {
    }

    #[\Override]
    public function checkMethodAllowed($obj, $method): void
    {
    }

    #[\Override]
    public function checkPropertyAllowed($obj, $property): void
    {
    }
}
