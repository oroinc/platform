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

    private array $skippedFilterKeys = [];

    /**
     * @param string   $operatorPattern
     * @param string[] $operators
     */
    public function __construct(string $operatorPattern, array $operators)
    {
        $this->operatorPattern = $operatorPattern;
        $this->operators = $operators;
    }

    public function addSkippedFilterKey(string $filterKey): void
    {
        $this->skippedFilterKeys[] = $filterKey;
    }

    /**
     * Creates new instance of the filter value accessor.
     */
    public function create(Request $request): FilterValueAccessorInterface
    {
        $filterValueAccessor = new RestFilterValueAccessor(
            $request,
            $this->operatorPattern,
            $this->operators
        );
        $filterValueAccessor->setSkippedFilterKeys($this->skippedFilterKeys);

        // the filter values can be sent in the request body only for the "delete_list" API action
        // or when the HTTP method is overridden, e.g. via the "X-HTTP-Method-Override" header (for example
        // for a case when GET HTTP request is sent via POST method due to a lot of filters
        // and the query string length limitation)
        if (Request::METHOD_DELETE === $request->getRealMethod()
            || $request->getMethod() !== $request->getRealMethod()
        ) {
            $filterValueAccessor->enableRequestBodyParsing();
        }

        return $filterValueAccessor;
    }
}
