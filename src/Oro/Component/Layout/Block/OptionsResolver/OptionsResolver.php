<?php

namespace Oro\Component\Layout\Block\OptionsResolver;

use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver as BaseOptionsResolver;

/**
 * Decorator class of `Symfony\Component\OptionsResolver\OptionsResolver` with removed methods, that not allowed for
 * using with expressions, like `setAllowedTypes` etc.
 * @see \Symfony\Component\OptionsResolver\OptionsResolver
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OptionsResolver implements Options
{
    private BaseOptionsResolver $optionsResolver;

    public function __construct()
    {
        $this->optionsResolver = new BaseOptionsResolver();
    }

    /**
     * @param $option
     * @param $value
     * @return $this
     */
    public function setDefault($option, $value)
    {
        $this->optionsResolver->setDefault($option, $value);

        return $this;
    }

    /**
     * @param array $defaults
     * @return $this
     */
    public function setDefaults(array $defaults)
    {
        $this->optionsResolver->setDefaults($defaults);

        return $this;
    }

    /**
     * @param $option
     * @return bool
     */
    public function hasDefault($option)
    {
        return $this->optionsResolver->hasDefault($option);
    }

    /**
     * @return array [name => value, ...]
     */
    public function getDefaults(): array
    {
        $reflClass = new \ReflectionClass($this->optionsResolver);
        $defaultsProperty = ReflectionUtil::getProperty($reflClass, 'defaults');
        if (null === $defaultsProperty) {
            throw new \RuntimeException(sprintf(
                'The class "%s" does not have property "defaults".',
                $reflClass->name
            ));
        }
        $defaultsProperty->setAccessible(true);

        return $defaultsProperty->getValue($this->optionsResolver);
    }

    /**
     * @param $optionNames
     * @return $this
     */
    public function setRequired($optionNames)
    {
        $this->optionsResolver->setRequired($optionNames);

        return $this;
    }

    /**
     * @param $option
     * @return bool
     */
    public function isRequired($option)
    {
        return $this->optionsResolver->isRequired($option);
    }

    /**
     * @return string[]
     */
    public function getRequiredOptions()
    {
        return $this->optionsResolver->getRequiredOptions();
    }

    /**
     * @param $option
     * @return bool
     */
    public function isMissing($option)
    {
        return $this->optionsResolver->isMissing($option);
    }

    /**
     * @return string[]
     */
    public function getMissingOptions()
    {
        return $this->optionsResolver->getMissingOptions();
    }

    /**
     * @param $optionNames
     * @return $this
     */
    public function setDefined($optionNames)
    {
        $this->optionsResolver->setDefined($optionNames);

        return $this;
    }

    /**
     * @param $option
     * @return bool
     */
    public function isDefined($option)
    {
        return $this->optionsResolver->isDefined($option);
    }

    /**
     * @return string[]
     */
    public function getDefinedOptions()
    {
        return $this->optionsResolver->getDefinedOptions();
    }

    /**
     * @param $optionNames
     * @return $this
     */
    public function remove($optionNames)
    {
        $this->optionsResolver->remove($optionNames);

        return $this;
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $this->optionsResolver->clear();

        return $this;
    }

    /**
     * @param array $options
     * @return array
     */
    public function resolve(array $options = [])
    {
        return $this->optionsResolver->resolve($options);
    }

    /**
     * @param $option
     * @return mixed
     * @throws \Exception
     */
    public function offsetGet($option): mixed
    {
        return $this->optionsResolver->offsetGet($option);
    }

    /**
     * @param $option
     * @return bool
     */
    public function offsetExists($option): bool
    {
        return $this->optionsResolver->offsetExists($option);
    }

    public function offsetSet($option, $value): void
    {
        $this->optionsResolver->offsetSet($option, $value);
    }

    public function offsetUnset($option): void
    {
        $this->optionsResolver->offsetUnset($option);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->optionsResolver->count();
    }

    public function __clone()
    {
        $this->optionsResolver = clone $this->optionsResolver;
    }
}
