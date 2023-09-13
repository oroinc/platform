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

    /**
     * {@inheritdoc}
     */
    public function getFirstArgument(): ?string
    {
        return current($this->arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function hasParameterOption($values, $onlyParams = false): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterOption($values, $default = false, $onlyParams = false)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function bind(InputDefinition $definition)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function validate()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument($name)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setArgument($name, $value)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hasArgument($name): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($name, $value)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption($name): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isInteractive(): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setInteractive($interactive)
    {
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->command;
    }
}
