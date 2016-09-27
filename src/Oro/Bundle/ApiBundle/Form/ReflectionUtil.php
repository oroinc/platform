<?php

namespace Oro\Bundle\ApiBundle\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\StringUtil;

class ReflectionUtil
{
    /**
     * Returns all possible add and remove methods.
     *
     * @param string $property The name of a property.
     *
     * @return array [[adder, remover], ...]
     */
    public static function getAdderAndRemoverNames($property)
    {
        $result = [];
        $camelized = self::camelize($property);
        $singulars = (array)StringUtil::singularify($camelized);
        foreach ($singulars as $singular) {
            $result[] = ['add' . $singular, 'remove' . $singular];
        }

        return $result;
    }

    /**
     * Searches for add and remove methods.
     *
     * @param string|object $object   Either a string containing the name of the class or an object.
     * @param string        $property The name of a property.
     *
     * @return array|null [adder, remover] when found, null otherwise
     */
    public static function findAdderAndRemover($object, $property)
    {
        $reflClass = new \ReflectionClass($object);
        $camelized = self::camelize($property);
        $singulars = (array)StringUtil::singularify($camelized);
        foreach ($singulars as $singular) {
            $addMethod = 'add' . $singular;
            $removeMethod = 'remove' . $singular;

            $addMethodFound = self::isMethodAccessible($reflClass, $addMethod, 1);
            $removeMethodFound = self::isMethodAccessible($reflClass, $removeMethod, 1);

            if ($addMethodFound && $removeMethodFound) {
                return [$addMethod, $removeMethod];
            }
        }

        return null;
    }

    /**
     * Removes all errors of the given form.
     *
     * @param FormInterface $form The form
     * @param bool          $deep Whether to clear errors of child forms as well
     */
    public static function clearFormErrors(FormInterface $form, $deep = false)
    {
        if (count($form->getErrors()) > 0) {
            $clearClosure = \Closure::bind(
                function (FormInterface $form) {
                    $form->errors = [];
                },
                null,
                $form
            );
            $clearClosure($form);
        }
        if ($deep) {
            foreach ($form as $childForm) {
                self::clearFormErrors($childForm);
            }
        }
    }

    /**
     * Returns whether a method is public and has the number of required parameters.
     *
     * @param \ReflectionClass $class      The class of the method
     * @param string           $methodName The method name
     * @param int              $parameters The number of parameters
     *
     * @return bool Whether the method is public and has $parameters
     *              required parameters
     */
    protected static function isMethodAccessible(\ReflectionClass $class, $methodName, $parameters)
    {
        if ($class->hasMethod($methodName)) {
            $method = $class->getMethod($methodName);
            if ($method->isPublic()
                && $method->getNumberOfRequiredParameters() <= $parameters
                && $method->getNumberOfParameters() >= $parameters
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Camelizes a given string.
     *
     * @param string $string Some string
     *
     * @return string The camelized version of the string
     */
    protected static function camelize($string)
    {
        return strtr(ucwords(strtr($string, ['_' => ' '])), [' ' => '']);
    }
}
