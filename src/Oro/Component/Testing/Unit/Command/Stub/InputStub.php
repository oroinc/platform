<?php

declare(strict_types=1);

namespace Oro\Component\Testing\Unit\Command\Stub;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Stub for Symfony\Component\Console\Input\InputInterface
 */
class InputStub implements InputInterface
{
    private string $command = '';

    private array $arguments = [];

    private array $options = [];

    private bool $interactive = false;

    public function __construct(?string $command = '', array $arguments = [], array $options = [])
    {
        $this->command = $command;
        $this->arguments = $arguments;
        $this->options = $options;
    }

    #[\Override]
    public function getFirstArgument(): ?string
    {
        return current($this->arguments);
    }

    #[\Override]
    public function hasParameterOption($values, $onlyParams = false): bool
    {
        $values = is_array($values) ? $values : [$values];

        foreach ($values as $value) {
            $str = (string)$value;
            $name = ltrim($str, '-');

            if (array_key_exists($name, $this->options)) {
                return true;
            }

            if (!$onlyParams
                && (array_key_exists($str, $this->arguments) || in_array($str, $this->arguments, true))
            ) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function getParameterOption($values, $default = false, $onlyParams = false): mixed
    {
        $values = is_array($values) ? $values : [$values];

        foreach ($values as $value) {
            $str = (string)$value;
            $name = ltrim($str, '-');

            if (array_key_exists($name, $this->options)) {
                return $this->options[$name];
            }

            if ($onlyParams) {
                continue;
            }

            if (array_key_exists($str, $this->arguments)) {
                return $this->arguments[$str];
            }

            if (in_array($str, $this->arguments, true)) {
                return $str;
            }
        }

        return $default;
    }

    #[\Override]
    public function bind(InputDefinition $definition): void
    {
    }

    #[\Override]
    public function validate(): void
    {
    }

    #[\Override]
    public function getArguments(): array
    {
        return $this->arguments;
    }

    #[\Override]
    public function getArgument($name): mixed
    {
        return $this->arguments[$name] ?? null;
    }

    #[\Override]
    public function setArgument($name, $value): void
    {
        $this->arguments[$name] = $value;
    }

    #[\Override]
    public function hasArgument($name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    #[\Override]
    public function getOptions(): array
    {
        return $this->options;
    }

    #[\Override]
    public function getOption($name): mixed
    {
        return $this->options[$name] ?? null;
    }

    #[\Override]
    public function setOption($name, $value): void
    {
        $this->options[$name] = $value;
    }

    #[\Override]
    public function hasOption($name): bool
    {
        return array_key_exists($name, $this->options);
    }

    #[\Override]
    public function isInteractive(): bool
    {
        return $this->interactive;
    }

    #[\Override]
    public function setInteractive($interactive): void
    {
        $this->interactive = (bool)$interactive;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString(): string
    {
        return (string)$this->command;
    }
}
