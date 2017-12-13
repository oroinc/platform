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
    public function getFirstArgument()
    {
        return current($this->arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function hasParameterOption($values)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterOption($values, $default = false)
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
    public function getArguments()
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
    public function hasArgument($name)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
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
    public function hasOption($name)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isInteractive()
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
