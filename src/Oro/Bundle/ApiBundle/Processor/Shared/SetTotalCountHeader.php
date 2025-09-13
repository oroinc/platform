<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Processor\Shared\Provider\TotalCountCalculator;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetRelationship\GetRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\QueryResolver;

/**
 * Calculates the total number of records and sets it
 * to "X-Include-Total-Count" response header
 * if it was requested by "X-Include: totalCount" request header.
 */
class SetTotalCountHeader implements ProcessorInterface
{
    public const RESPONSE_HEADER_NAME = 'X-Include-Total-Count';
    public const REQUEST_HEADER_VALUE = 'totalCount';

    private CountQueryBuilderOptimizer $countQueryBuilderOptimizer;
    private QueryResolver $queryResolver;

    public function __construct(
        CountQueryBuilderOptimizer $countQueryOptimizer,
        QueryResolver $queryResolver
    ) {
        $this->countQueryBuilderOptimizer = $countQueryOptimizer;
        $this->queryResolver = $queryResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext|GetRelationshipContext|GetSubresourceContext $context */

        if ($context->getResponseHeaders()->has(self::RESPONSE_HEADER_NAME)) {
            // total count header is already set
            return;
        }

        $xInclude = $context->getRequestHeaders()->get(Context::INCLUDE_HEADER);
        if (empty($xInclude) || !\in_array(self::REQUEST_HEADER_VALUE, $xInclude, true)) {
            // total count is not requested
            return;
        }

        $totalCount = $this->getTotalCount($context, $context->getTotalCountCallback());
        if (null !== $totalCount) {
            $context->getResponseHeaders()->set(self::RESPONSE_HEADER_NAME, $totalCount);
        }
    }

    private function getTotalCount(ContextInterface $context, ?callable $totalCountCallback): ?int
    {
        if (null !== $totalCountCallback) {
            return $this->getTotalCountCalculator()->executeTotalCountCallback($totalCountCallback);
        }

        if ($context->getAction() !== ApiAction::DELETE_LIST && $context->getConfig()?->getPageSize() === -1) {
            // the paging is disabled, no need to execute a separate DB query to calculate total count
            return $this->calculateResultCount($context);
        }

        $query = $context->getQuery();
        if (null === $query) {
            return $this->calculateResultCount($context);
        }

        return $this->getTotalCountCalculator()->calculateTotalCount($query, $context->getConfig());
    }

    private function getTotalCountCalculator(): TotalCountCalculator
    {
        return new TotalCountCalculator($this->countQueryBuilderOptimizer, $this->queryResolver);
    }

    private function calculateResultCount(Context $context): ?int
    {
        if (!$context->hasResult()) {
            return null;
        }

        $data = $context->getResult();
        if (!\is_array($data)) {
            return null;
        }

        return \count($data);
    }
}
