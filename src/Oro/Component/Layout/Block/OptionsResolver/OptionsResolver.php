<?php

namespace Oro\Component\Layout\Block\OptionsResolver;

use Symfony\Component\OptionsResolver\OptionsResolver as BaseOptionsResolver;

/**
 * Decorator class of `Symfony\Component\OptionsResolver\OptionsResolver` with removed methods, that not allowed for
 * using with expressions, like `setAllowedTypes` etc.
 * @see Symfony\Component\OptionsResolver\OptionsResolver
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OptionsResolver
{
    /**
     * @var BaseOptionsResolver $optionsResolver
     */
    protected $optionsResolver;

    /**
     * Instantiate Symfony option resolver.
     */
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
    public function offsetGet($option)
    {
        return $this->optionsResolver->offsetGet($option);
    }

    /**
     * @param $option
     * @return bool
     */
    public function offsetExists($option)
    {
        return $this->optionsResolver->offsetExists($option);
    }

    /**
     * @param $option
     * @param $value
     */
    public function offsetSet($option, $value)
    {
        $this->optionsResolver->offsetSet($option, $value);
    }

    /**
     * @param $option
     */
    public function offsetUnset($option)
    {
        $this->optionsResolver->offsetUnset($option);
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->optionsResolver->count();
    }
}
