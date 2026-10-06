<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Twig\Sandbox\SecurityError;
use Twig\Source;

/**
 * Event dispatched when the Twig sandbox denies an attribute access while an email template is being rendered.
 */
class EmailTemplateSecurityPolicyViolationEvent extends Event
{
    private mixed $value = null;

    private bool $handled = false;

    /**
     * @param SecurityError $violation The sandbox error that denied the access.
     * @param mixed $object The object the attribute was read from.
     * @param mixed $item The name of the denied method or property.
     * @param array $arguments The arguments the denied method was called with.
     * @param string $type The Twig attribute access type: "any", "method" or "array".
     * @param bool $isDefinedTest Whether the access comes from an "is defined" test rather than from a read.
     * @param Source $source The source of the template being rendered.
     * @param int $lineno The line of the template the access is on.
     * @param array $context The Twig context the template is being rendered with, see {@see self::getContext()}.
     */
    public function __construct(
        private readonly SecurityError $violation,
        private readonly mixed $object,
        private readonly mixed $item,
        private readonly array $arguments,
        private readonly string $type,
        private readonly bool $isDefinedTest,
        private readonly Source $source,
        private readonly int $lineno,
        #[\SensitiveParameter] private readonly array $context = []
    ) {
    }

    public function getViolation(): SecurityError
    {
        return $this->violation;
    }

    public function getObject(): mixed
    {
        return $this->object;
    }

    public function getItem(): mixed
    {
        return $this->item;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Whether the denied attribute was only tested for existence, as "x is defined" and the "default" filter do,
     * rather than read. A substituted value then decides the answer of that test: non-null means defined.
     */
    public function isDefinedTest(): bool
    {
        return $this->isDefinedTest;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function getLineno(): int
    {
        return $this->lineno;
    }

    /**
     * Returns the Twig context the template is being rendered with: every variable the template could see at the
     * point of the denied access, template parameters and {% set %} and loop variables alike.
     *
     * May include sensitive data, so it should never be included in logs.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Returns the value the denied attribute resolves to, null unless a listener substituted one.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Substitutes the value the denied attribute resolves to and marks the violation as handled.
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
        $this->handled = true;
    }

    /**
     * Whether a listener already substituted a value.
     */
    public function isHandled(): bool
    {
        return $this->handled;
    }

    public function setHandled(bool $handled): void
    {
        $this->handled = $handled;
    }
}
