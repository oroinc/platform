<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;

/**
 * Loads entity using ORM QueryBuilder or ORM Query object.
 * IMPORTANT: this processor does not apply access rules to the query.
 */
class LoadEntityByOrmQuery implements ProcessorInterface
{
    private QueryHintResolverInterface $queryHintResolver;

    public function __construct(QueryHintResolverInterface $queryHintResolver)
    {
        $this->queryHintResolver = $queryHintResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasResult()) {
            // result data are already retrieved
            return;
        }

        $query = $context->getQuery();
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
            $this->queryHintResolver->resolveHints($query, $this->getHints($context->getConfig()));
            $context->setResult($query->getOneOrNullResult());
        } elseif ($query instanceof Query) {
            $this->queryHintResolver->resolveHints($query, $this->getHints($context->getConfig()));
            $context->setResult($query->getOneOrNullResult());
        }
    }

    private function getHints(?EntityDefinitionConfig $config): array
    {
        if (null === $config) {
            return [];
        }

        return $config->getHints();
    }
}
