<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Loads entity using ORM QueryBuilder object.
 */
class LoadEntityByOrmQuery implements ProcessorInterface
{
    /** @var QueryHintResolverInterface */
    protected $queryHintResolver;

    /**
     * @param QueryHintResolverInterface $queryHintResolver
     */
    public function __construct(QueryHintResolverInterface $queryHintResolver)
    {
        $this->queryHintResolver = $queryHintResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasResult()) {
            // result data are already retrieved
            return;
        }

        $query = $context->getQuery();
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
            $this->queryHintResolver->resolveHints($query, $context->getConfig()->getHints());
            $context->setResult($query->getOneOrNullResult());
        } elseif ($query instanceof Query) {
            $this->queryHintResolver->resolveHints($query, $context->getConfig()->getHints());
            $context->setResult($query->getOneOrNullResult());
        }
    }
}
