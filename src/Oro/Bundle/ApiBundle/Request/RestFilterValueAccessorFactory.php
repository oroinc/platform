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
    public function create(Request $request, string $action): FilterValueAccessorInterface
    {
        $filterValueAccessor = new RestFilterValueAccessor($request, $this->operators);
        // the filter values can be sent in the request body only for the "delete_list" API action
        // or when the HTTP method is overridden, e.g. via the "X-HTTP-Method-Override" header (for example
        // for a case when GET HTTP request is sent via POST method due to a lot of filters
        // and the query string length limitation)
        if (ApiAction::DELETE_LIST === $action || $request->getMethod() !== $request->getRealMethod()) {
            $filterValueAccessor->enableRequestBodyParsing();
        }

        return $filterValueAccessor;
    }
}
