<?php

namespace Oro\Bundle\LayoutBundle\Command\Util;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DebugOptionsResolver extends OptionsResolver
{
    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->getPrivatePropertyValue($this, 'defaults');
    }

    /**
     * @return array
     */
    public function getDefinedOptions()
    {
        $definedOptions = $this->getPrivatePropertyValue($this, 'defined');
        $allowedTypes   = $this->getPrivatePropertyValue($this, 'allowedTypes');

        $result = [];
        foreach (array_keys($definedOptions) as $name) {
            $result[$name] = isset($allowedTypes[$name]) ? (array)$allowedTypes[$name] : [];
        }

        return $result;
    }

    /**
     * @param object $object
     * @param string $propertyName
     *
     * @return mixed
     */
    private function getPrivatePropertyValue($object, $propertyName)
    {
        $reflClass = new \ReflectionClass($object);
        $prop      = null;
        while ($reflClass) {
            if ($reflClass->hasProperty($propertyName)) {
                $prop = $reflClass->getProperty($propertyName);
                break;
            }
            $reflClass = $reflClass->getParentClass();
        }
        if (!$prop) {
            throw new \RuntimeException(sprintf('Property "%s" does not exist.', $propertyName));
        }

        $prop->setAccessible(true);

        return $prop->getValue($object);
    }
}
