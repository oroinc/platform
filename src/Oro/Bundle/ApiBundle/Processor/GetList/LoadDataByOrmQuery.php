<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads data using ORM QueryBuilder object.
 */
class LoadDataByOrmQuery implements ProcessorInterface
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
        if ($query instanceof QueryBuilder) {
            $context->setResult($query->getQuery()->getResult());
        } elseif ($query instanceof Query) {
            $context->setResult($query->getResult());
        }
    }
}
