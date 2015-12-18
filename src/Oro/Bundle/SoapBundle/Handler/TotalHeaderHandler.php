<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\DoctrineUtils\ORM\SqlQuery;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestApiReadInterface;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;

class TotalHeaderHandler implements IncludeHandlerInterface
{
    const HEADER_NAME = 'X-Include-Total-Count';

    /** @var CountQueryBuilderOptimizer */
    protected $countQueryBuilderOptimizer;

    /**
     * @param CountQueryBuilderOptimizer $countQueryOptimizer
     */
    public function __construct(CountQueryBuilderOptimizer $countQueryOptimizer)
    {
        $this->countQueryBuilderOptimizer = $countQueryOptimizer;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Context $context)
    {
        return
            $context->isAction(RestApiReadInterface::ACTION_LIST)
            && (
                $context->has('query')
                || $context->has('totalCount')
                || $context->getController() instanceof EntityManagerAwareInterface
            );
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Context $context)
    {
        if ($context->has('totalCount')) {
            $totalCount = $context->get('totalCount');
            if (!is_callable($totalCount)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Expected callable for totalCount, "%s" given',
                        is_object($totalCount) ? get_class($totalCount) : gettype($totalCount)
                    )
                );
            }
            $totalCount = call_user_func($totalCount);
            if (!is_int($totalCount)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Expected integer as result of totalCount callable, "%s" given',
                        is_object($totalCount) ? get_class($totalCount) : gettype($totalCount)
                    )
                );
            }
        } else {
            if ($context->has('query')) {
                $value = $context->get('query');

                if ($value instanceof QueryBuilder) {
                    $countQb = $this->countQueryBuilderOptimizer->getCountQueryBuilder($value);
                    $query   = $countQb->getQuery();
                } elseif ($value instanceof Query) {
                    $query = clone $value;
                    $query->setMaxResults(null)->setFirstResult(null);
                } elseif ($value instanceof SqlQueryBuilder) {
                    $query = clone $value;
                    $query->setMaxResults(null)->setFirstResult(null);
                    $query = $query->getQuery();
                } elseif ($value instanceof SqlQuery) {
                    $query = clone $value;
                    $query->getQueryBuilder()->setMaxResults(null)->setFirstResult(null);
                } else {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Expected instance of Doctrine\ORM\QueryBuilder, Doctrine\ORM\Query'
                            . ', Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder'
                            . ' or Oro\Component\DoctrineUtils\ORM\SqlQuery, "%s" given',
                            is_object($value) ? get_class($value) : gettype($value)
                        )
                    );
                }
            } else {
                $qb    = $context->getController()->getManager()->getRepository()->createQueryBuilder('e');
                $query = $qb->getQuery();
            }

            $totalCount = $this->calculateCount($query);
        }

        $context->getResponse()->headers->set(self::HEADER_NAME, $totalCount);
    }

    /**
     * @param mixed $query
     *
     * @return int
     */
    protected function calculateCount($query)
    {
        return QueryCountCalculator::calculateCount($query);
    }
}
