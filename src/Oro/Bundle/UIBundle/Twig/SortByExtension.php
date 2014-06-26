<?php

namespace Oro\Bundle\UIBundle\Twig;

use Symfony\Component\PropertyAccess\PropertyAccess;

class SortByExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_sort_by', [$this, 'sortBy'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_sort_by';
    }

    /**
     * Sorts an array by specified property.
     *
     * Supported options:
     *  reverse  [boolean]   Indicates whether the sorting should be performed in reverse order
     *  default  [mixed]     The default property value if it is NULL
     *  type     [string]    The comparison type. It can be used to select proper comparison method
     *  case     [boolean]   Indicates whether case-insensitive string comparison should be used or not
     *
     * @param array  $array        The array to be sorted
     * @param string $propertyPath The path of the property by which the array should be sorted
     * @param array  $options      The sorting options
     *
     * @return array The sorted array
     */
    public function sortBy(array $array, $propertyPath, array $options = [])
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $comparisonType   = $this->getOption($options, 'type', 'number');
        $compareMethodName = $comparisonType . 'Compare';
        uasort(
            $array,
            function ($a, $b) use ($propertyAccessor, $propertyPath, $options, $compareMethodName) {
                $aVal = $propertyAccessor->getValue($a, $propertyPath);
                $bVal = $propertyAccessor->getValue($b, $propertyPath);

                return $this->{$compareMethodName}($aVal, $bVal, $options);
            }
        );

        return $array;
    }

    /**
     * @param mixed $aVal
     * @param mixed $bVal
     * @param array $options
     * @return bool
     */
    protected function numberCompare($aVal, $bVal, $options)
    {
        $default = isset($options['default']) ? $options['default'] : 0;
        if (null === $aVal) {
            $aVal = $default;
        }
        if (null === $bVal) {
            $bVal = $default;
        }

        return $this->getOption($options, 'reverse', false)
            ? $aVal < $bVal
            : $aVal > $bVal;
    }

    /**
     * @param string|null $aVal
     * @param string|null $bVal
     * @param array       $options
     *
     * @return int
     */
    protected function stringCompare($aVal, $bVal, $options)
    {
        $default = isset($options['default']) ? $options['default'] : '';
        if (null === $aVal) {
            $aVal = $default;
        }
        if (null === $bVal) {
            $bVal = $default;
        }

        if ($aVal === $bVal) {
            return 0;
        }

        if ($this->getOption($options, 'case', false)) {
            $result = strcasecmp($aVal, $bVal);
        } else {
            $result = strcmp($aVal, $bVal);
        }
        if ($this->getOption($options, 'reverse', false)) {
            $result = 0 - $result;
        }

        return $result;
    }

    /**
     * @param array  $options
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    protected function getOption($options, $name, $defaultValue = null)
    {
        return isset($options[$name]) ? $options[$name] : $defaultValue;
    }
}
