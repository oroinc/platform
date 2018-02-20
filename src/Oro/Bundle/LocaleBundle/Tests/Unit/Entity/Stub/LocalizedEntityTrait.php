<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;

trait LocalizedEntityTrait
{
    use FallbackTrait;

    /**
     * @param array $definition
     * @param string $name
     * @param string $value
     * @return $this
     */
    protected function localizedFieldSet(array $definition, $name, $value)
    {
        $setter = $this->getLocalizationMethodName($name, 'get');

        foreach ($definition as $singularName => $propertyName) {
            $defaultSetterName = $this->getLocalizationMethodName($singularName, 'getDefault');

            switch ($setter) {
                case $defaultSetterName:
                    return $this->setDefaultFallbackValue($this->$propertyName, $value);
                break;
            }
        }

        return null;
    }

    /**
     * @param array $definition
     * @param string $name
     * @return mixed
     */
    protected function localizedFieldGet(array $definition, $name)
    {
        $getter = $this->getLocalizationMethodName($name, 'get');

        foreach ($definition as $singularName => $propertyName) {
            $getterName = $this->getLocalizationMethodName($singularName, 'get');
            $defaultGetterName = $this->getLocalizationMethodName($singularName, 'getDefault');

            switch ($getter) {
                case $getterName:
                case $defaultGetterName:
                    return $this->getFallbackValue($this->$propertyName);
                    break;
                default:
                    break;
            }
        }

        return null;
    }

    /**
     * @param array $definition
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    protected function localizedMethodCall(array $definition, $name, array $arguments)
    {
        $value = isset($arguments[0]) ? $arguments[0] : null;

        foreach ($definition as $singularName => $propertyName) {
            $getterName = $this->getLocalizationMethodName($singularName, 'get');
            $defaultGetterName = $this->getLocalizationMethodName($singularName, 'getDefault');
            $defaultSetterName = $this->getLocalizationMethodName($singularName, 'setDefault');

            switch ($name) {
                case $getterName:
                case $defaultGetterName:
                    return $this->getFallbackValue($this->$propertyName, $value);
                    break;
                case $defaultSetterName:
                    return $this->setDefaultFallbackValue($this->$propertyName, $value);
                    break;
                default:
                    break;
            }
        }

        trigger_error('Call to undefined method '.__CLASS__.'::'.$name.'()', E_USER_ERROR);

        return null;
    }

    /**
     * @param string $fieldName
     * @param string $prefix
     * @return string
     */
    protected function getLocalizationMethodName($fieldName, $prefix)
    {
        return $prefix . ucfirst(Inflector::camelize($fieldName));
    }
}
