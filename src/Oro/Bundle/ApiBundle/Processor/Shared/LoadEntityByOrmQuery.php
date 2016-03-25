<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Loads entity using ORM QueryBuilder object.
 */
class LoadEntityByOrmQuery implements ProcessorInterface
{
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
            $context->setResult($query->getQuery()->getOneOrNullResult());
        } elseif ($query instanceof Query) {
            $context->setResult($query->getOneOrNullResult());
        }
    }
}
