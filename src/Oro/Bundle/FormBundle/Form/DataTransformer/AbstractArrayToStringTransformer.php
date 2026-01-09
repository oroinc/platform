<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Provides common functionality for transforming between arrays and delimited strings.
 *
 * This base class handles bidirectional transformation between array values and string representations
 * using a configurable delimiter. It supports optional filtering of unique values and provides
 * template methods for subclasses to customize the transformation logic.
 */
abstract class AbstractArrayToStringTransformer implements DataTransformerInterface
{
    /**
     * @var bool
     */
    private $filterUniqueValues;

    /**
     * @var string
     */
    protected $delimiter;

    /**
     * @param string $delimiter
     * @param boolean $filterUniqueValues
     */
    public function __construct($delimiter, $filterUniqueValues)
    {
        $this->delimiter = $delimiter;
        $this->filterUniqueValues = $filterUniqueValues;
    }

    /**
     * Transforms string to array
     *
     * @param string $stringValue
     *
     * @return array
     */
    protected function transformStringToArray($stringValue)
    {
        if (trim($this->delimiter)) {
            $separator = trim($this->delimiter);
        } else {
            $separator = $this->delimiter;
        }
        $arrayValue = explode($separator, $stringValue);
        return $this->filterArrayValue($arrayValue);
    }

    /**
     * Transforms array to string
     *
     * @param array $arrayValue
     *
     * @return string
     */
    protected function transformArrayToString(array $arrayValue)
    {
        if (trim($this->delimiter)) {
            $separator = trim($this->delimiter);
        } else {
            $separator = $this->delimiter;
        }
        return implode($separator, $this->filterArrayValue($arrayValue));
    }

    /**
     * Trims all elements and apply unique filter if needed
     *
     * @param array $arrayValue
     * @return array
     */
    private function filterArrayValue(array $arrayValue)
    {
        if ($this->filterUniqueValues) {
            $arrayValue = array_unique($arrayValue);
        }
        $arrayValue = array_filter(array_map('trim', $arrayValue));
        return array_values($arrayValue);
    }
}
