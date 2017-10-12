<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

/**
 * Contains all data transformers
 * and allows to get a transformer suitable for a specific request type.
 * @see \Oro\Component\EntitySerializer\EntityDataTransformer
 */
class DataTransformerRegistry
{
    /** @var array [data type => [[transformer, request type expression], ...], ...] */
    private $transformers;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /**
     * @param array                    $transformers [data type => [[transformer, request type expression], ...], ...]
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(array $transformers, RequestExpressionMatcher $matcher)
    {
        $this->transformers = $transformers;
        $this->matcher = $matcher;
    }

    /**
     * Returns a data transformer for a given data type.
     *
     * @param string      $dataType
     * @param RequestType $requestType
     *
     * @return mixed|null Can be NULL,
     *                    an instance of "Oro\Component\EntitySerializer\DataTransformerInterface"
     *                    or "Symfony\Component\Form\DataTransformerInterface".
     */
    public function getDataTransformer($dataType, RequestType $requestType)
    {
        $result = null;
        if (isset($this->transformers[$dataType])) {
            foreach ($this->transformers[$dataType] as list($transformer, $expression)) {
                if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                    $result = $transformer;
                    break;
                }
            }
        }

        return $result;
    }
}
