<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\SqlQuery;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;

/**
 * Loads data using SqlQueryBuilder object.
 */
class LoadDataBySqlQuery implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        if ($context->hasResult()) {
            // result data are already retrieved
            return;
        }

        $query = $context->getQuery();
        if ($query instanceof SqlQueryBuilder) {
            $context->setResult($query->getQuery()->getResult());
        } elseif ($query instanceof SqlQuery) {
            $context->setResult($query->getResult());
        }
    }
}
