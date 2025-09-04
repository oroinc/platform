<?php

namespace Oro\Bundle\ApiBundle\Model;

use Oro\Bundle\ApiBundle\Processor\ContextInterface;

/**
 * Represents a service that is used to wrap execution of queries that load identifiers of entities
 * to catch possible exceptions and add them as validation errors in the context.
 */
interface LoadEntityIdsQueryExecutorInterface
{
    public function execute(ContextInterface $context, callable $callback): mixed;
}
