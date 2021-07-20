<?php

namespace Oro\Bundle\LayoutBundle\Command\Util;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * Decorator for layout option resolver that helps to get information about all registered options.
 */
class DebugOptionsResolverDecorator
{
    public const NO_VALUE = 'NO_VALUE';

    /**
     * @var OptionsResolver
     */
    protected $optionsResolver;

    public function __construct(OptionsResolver $optionsResolver)
    {
        $this->optionsResolver = $optionsResolver;
    }

    public function getOptionResolver(): OptionsResolver
    {
        return $this->optionsResolver;
    }

    public function getDefaultOptions(): array
    {
        return $this->getOptionPropertyValue($this->optionsResolver, 'defaults');
    }

    public function getRequiredOptions(): array
    {
        return $this->getOptionPropertyValue($this->optionsResolver, 'required');
    }

    public function getDefinedOptions(): array
    {
        return array_keys($this->getOptionPropertyValue($this->optionsResolver, 'defined'));
    }

    public function getOptions(): array
    {
        $defaultOptions = $this->getDefaultOptions();
        $requiredOptions = $this->getRequiredOptions();
        $definedOptions = $this->getDefinedOptions();
        $options = [];

        foreach ($definedOptions as $key => $name) {
            $required = array_key_exists($name, $requiredOptions) && $requiredOptions[$name] === true;
            $hasDefaultValue = array_key_exists($name, $defaultOptions);
            $option = [
                'name' => $name,
                'defaultValue' => $hasDefaultValue ? $defaultOptions[$name] : self::NO_VALUE,
                'required' => $required
            ];
            // sort options to show required with default first
            if ($required && $hasDefaultValue) {
                $key = 10000 + $key;
            } elseif ($required) {
                $key = 20000 + $key;
            } elseif ($hasDefaultValue) {
                $key = 30000 + $key;
            } else {
                $key = 40000 + $key;
            }
            $options[$key] = $option;
        }
        ksort($options);

        return $options;
    }

    /**
     * @param $optionResolver
     * @param $propertyName
     * @return mixed
     * @throws \ReflectionException
     */
    private function getOptionPropertyValue($optionResolver, $propertyName)
    {
        $baseOptionsResolver = $this->getPrivatePropertyValue($optionResolver, 'optionsResolver');

        return $this->getPrivatePropertyValue($baseOptionsResolver, $propertyName);
    }

    /**
     * @param OptionsResolver $object
     * @param string                             $propertyName
     *
     * @return mixed
     * @throws \ReflectionException
     */
    private function getPrivatePropertyValue($object, $propertyName)
    {
        $property = ReflectionUtil::getProperty(new \ReflectionClass($object), $propertyName);
        if (!$property) {
            throw new \RuntimeException(sprintf('Property "%s" does not exist.', $propertyName));
        }

        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
