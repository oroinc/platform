<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation;

/**
 * Base implementation of {@see SecurityPolicyViolationInterface}.
 */
abstract class AbstractSecurityPolicyViolation implements SecurityPolicyViolationInterface
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $variableName,
        private readonly ?string $entityClass,
        private readonly int $templateLine,
        private readonly \Throwable $cause,
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getVariableName(): ?string
    {
        return $this->variableName;
    }

    #[\Override]
    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    #[\Override]
    public function getTemplateLine(): int
    {
        return $this->templateLine;
    }

    #[\Override]
    public function getCause(): \Throwable
    {
        return $this->cause;
    }
}
