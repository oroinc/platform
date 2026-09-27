<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

use Twig\Error\RuntimeError;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * A stub security policy that fails a method check with a RuntimeError instead of a SecurityNotAllowedMethodError,
 * used to verify that a failure other than a sandbox method denial is not turned into a substituted value.
 */
class SecurityPolicyThrowingRuntimeErrorStub implements SecurityPolicyInterface
{
    public const string ERROR_MESSAGE = 'Security policy failure that is not a method denial.';

    #[\Override]
    public function checkSecurity($tags, $filters, $functions): void
    {
    }

    #[\Override]
    public function checkMethodAllowed($obj, $method): void
    {
        throw new RuntimeError(self::ERROR_MESSAGE);
    }

    #[\Override]
    public function checkPropertyAllowed($obj, $property): void
    {
    }
}
