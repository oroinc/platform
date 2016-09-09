<?php

namespace Oro\Bundle\LayoutBundle\Command\Util;

use Symfony\Component\OptionsResolver\OptionsResolver as BaseOptionResolver;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\PhpUtils\ReflectionUtil;

class DebugOptionsResolverDecorator
{
    /**
     * @var OptionsResolver|BaseOptionResolver
     */
    protected $optionsResolver;

    /**
     * @param OptionsResolver|BaseOptionResolver $optionsResolver
     */
    public function __construct(BaseOptionResolver $optionsResolver)
    {
        $this->optionsResolver = $optionsResolver;
    }

    /**
     * @return OptionsResolver|BaseOptionResolver
     */
    public function getOptionResolver()
    {
        return $this->optionsResolver;
    }

    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->getPrivatePropertyValue($this->optionsResolver, 'defaults');
    }

    /**
     * @return array
     */
    public function getDefinedOptions()
    {
        $definedOptions = array_keys($this->getPrivatePropertyValue($this->optionsResolver, 'defined'));
        $allowedTypes   = $this->getPrivatePropertyValue($this->optionsResolver, 'allowedTypes');

        $result = [];
        foreach ($definedOptions as $name) {
            $result[$name] = isset($allowedTypes[$name]) ? (array)$allowedTypes[$name] : [];
        }

        return $result;
    }

    /**
     * @param OptionsResolver|BaseOptionResolver $object
     * @param string $propertyName
     *
     * @return mixed
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
