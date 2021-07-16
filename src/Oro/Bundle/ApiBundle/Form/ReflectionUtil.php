<?php

namespace Oro\Bundle\ApiBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Inflector\Inflector;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * A set of utility methods for performing reflective operations are used in API forms.
 */
class ReflectionUtil
{
    /**
     * Returns all possible add and remove methods.
     *
     * @param string $property The name of a property.
     *
     * @return array [[adder, remover], ...]
     */
    public static function getAdderAndRemoverNames(string $property): array
    {
        $result = [];
        $camelized = self::camelize($property);
        $singulars = (array)Inflector::singularize($camelized);
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
    public static function findAdderAndRemover($object, string $property): ?array
    {
        $reflClass = new \ReflectionClass($object);
        $camelized = self::camelize($property);
        $singulars = (array)Inflector::singularize($camelized);
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
     * Marks all children of the given form as submitted.
     * Not submitted compound forms with "required" option equals to FALSE
     * and not required to be filled out simple forms will not be marked as submitted
     * to avoid validation of them.
     */
    public static function markFormChildrenAsSubmitted(
        FormInterface $form,
        PropertyAccessorInterface $propertyAccessor
    ): void {
        foreach ($form as $child) {
            if (!$child instanceof Form) {
                continue;
            }
            $hasChildren = ($child->count() > 0);
            if (!$child->isSubmitted()
                && (
                    ($hasChildren && $child->getConfig()->getRequired())
                    || (!$hasChildren && $child->isRequired())
                )
            ) {
                $markClosure = \Closure::bind(
                    function ($form, $data) {
                        $form->submitted = true;
                        $form->modelData = $data;
                    },
                    null,
                    $child
                );
                $markClosure($child, self::getDataForSubmittedForm($child, $propertyAccessor));
            }
            if ($hasChildren) {
                self::markFormChildrenAsSubmitted($child, $propertyAccessor);
            }
        }
    }

    /**
     * Gets the given form data that should be set together with marking the form as submitted.
     *
     * @param FormInterface             $form
     * @param PropertyAccessorInterface $propertyAccessor
     *
     * @return mixed
     */
    private static function getDataForSubmittedForm(FormInterface $form, PropertyAccessorInterface $propertyAccessor)
    {
        $config = $form->getConfig();
        if (!$config->getMapped() || $config->getInheritData()) {
            return null;
        }
        $parent = $form->getParent();
        if (null === $parent) {
            return null;
        }

        $parentData = $parent->getData();

        return \is_object($parentData) || \is_array($parentData)
            ? $propertyAccessor->getValue($parentData, $form->getPropertyPath())
            : null;
    }

    /**
     * Indicates whether a method is public and has the number of required parameters.
     *
     * @param \ReflectionClass $class      The class of the method
     * @param string           $methodName The method name
     * @param int              $parameters The number of parameters
     *
     * @return bool Whether the method is public and has $parameters
     *              required parameters
     */
    private static function isMethodAccessible(\ReflectionClass $class, string $methodName, int $parameters): bool
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
    private static function camelize(string $string): string
    {
        return \strtr(\ucwords(\strtr($string, ['_' => ' '])), [' ' => '']);
    }
}
