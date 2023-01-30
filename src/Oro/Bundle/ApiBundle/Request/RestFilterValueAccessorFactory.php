<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates new instance of the filter value accessor that is used to extract filters from REST API HTTP Request.
 */
class RestFilterValueAccessorFactory
{
    private string $operatorPattern;
    /** @var string[] [operator name => operator, ...] */
    private array $operators;

    /**
     * @param string   $operatorPattern
     * @param string[] $operators
     */
    public function __construct(string $operatorPattern, array $operators)
    {
        $this->operatorPattern = $operatorPattern;
        $this->operators = $operators;
    }

    /**
     * Creates new instance of the filter value accessor.
     */
    public function create(Request $request): FilterValueAccessorInterface
    {
        return new RestFilterValueAccessor(
            $request,
            $this->operatorPattern,
            $this->operators
        );
    }
}
