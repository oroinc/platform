<?php

namespace Oro\Bundle\LayoutBundle\Command\Util;

use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\OptionsResolver\Options;

/**
 * Decorator for layout option resolver that helps to get information about all registered options.
 */
class DebugOptionsResolverDecorator
{
    public const NO_VALUE = 'NO_VALUE';

    /**
     * @var Options
     */
    protected $optionsResolver;

    public function __construct(Options $optionsResolver)
    {
        $this->optionsResolver = $optionsResolver;
    }

    public function getOptionResolver(): Options
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
    protected function getOptionPropertyValue($optionResolver, $propertyName)
    {
        $baseOptionsResolver = $this->getPrivatePropertyValue($optionResolver, 'optionsResolver');

        return $this->getPrivatePropertyValue($baseOptionsResolver, $propertyName);
    }

    /**
     * @param Options $object
     * @param string                             $propertyName
     *
     * @return mixed
     * @throws \ReflectionException
     */
    protected function getPrivatePropertyValue($object, $propertyName)
    {
        $property = ReflectionUtil::getProperty(new \ReflectionClass($object), $propertyName);
        if (!$property) {
            throw new \RuntimeException(sprintf('Property "%s" does not exist.', $propertyName));
        }

        return $property->getValue($object);
    }
}
