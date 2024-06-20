<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates new instance of the filter value accessor that is used to extract filters from REST API HTTP Request.
 */
class RestFilterValueAccessorFactory
{
    /** @var string[] [operator name => operator, ...] */
    private array $operators;

    /**
     * @param string[] $operators
     */
    public function __construct(array $operators)
    {
        $this->operators = $operators;
    }

    /**
     * Creates new instance of the filter value accessor.
     */
    public function create(Request $request): FilterValueAccessorInterface
    {
        return new RestFilterValueAccessor($request, $this->operators);
    }
}
