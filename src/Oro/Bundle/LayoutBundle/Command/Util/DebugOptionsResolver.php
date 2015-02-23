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
        $defaultOptions = $this->getPrivatePropertyValue($this, 'defaultOptions');

        return $this->getPrivatePropertyValue($defaultOptions, 'options');
    }

    /**
     * @return string[]
     */
    public function getKnownOptions()
    {
        $knownOptions = $this->getPrivatePropertyValue($this, 'knownOptions');

        return array_keys($knownOptions);
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
            throw new \RuntimeException(sprintf('Property "%s" does not exist.' . $propertyName));
        }

        $prop->setAccessible(true);

        return $prop->getValue($object);
    }
}
