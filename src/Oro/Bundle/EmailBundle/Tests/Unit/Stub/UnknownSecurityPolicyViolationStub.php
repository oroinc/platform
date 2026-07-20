<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyViolationInterface;

class UnknownSecurityPolicyViolationStub implements SecurityPolicyViolationInterface
{
    #[\Override]
    public function getName(): string
    {
        return 'unknown';
    }

    #[\Override]
    public function getVariableName(): ?string
    {
        return null;
    }

    #[\Override]
    public function getEntityClass(): ?string
    {
        return null;
    }

    #[\Override]
    public function getTemplateLine(): int
    {
        return 1;
    }

    #[\Override]
    public function getCause(): \Throwable
    {
        return new \RuntimeException();
    }
}
