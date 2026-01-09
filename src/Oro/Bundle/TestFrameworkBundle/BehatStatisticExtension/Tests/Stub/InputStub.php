<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Stub;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;

class InputStub implements InputInterface
{
    /** @var string */
    private $command = '';

    /** @var array */
    private $arguments = [];

    /** @var array */
    private $options = [];

    /**
     * @param string $command
     * @param array $arguments
     * @param array $options
     */
    public function __construct($command = '', array $arguments = [], array $options = [])
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
    }

    #[\Override]
    public function getParameterOption($values, $default = false, $onlyParams = false): mixed
    {
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
    }

    #[\Override]
    public function setArgument($name, $value): void
    {
    }

    #[\Override]
    public function hasArgument($name): bool
    {
    }

    #[\Override]
    public function getOptions(): array
    {
        return $this->options;
    }

    #[\Override]
    public function getOption($name): mixed
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        return null;
    }

    #[\Override]
    public function setOption($name, $value): void
    {
    }

    #[\Override]
    public function hasOption($name): bool
    {
    }

    #[\Override]
    public function isInteractive(): bool
    {
    }

    #[\Override]
    public function setInteractive($interactive): void
    {
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
