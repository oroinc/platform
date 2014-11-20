<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

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
        $controller = $context->getController();

        return ($controller instanceof EntityManagerAwareInterface || $context->has('query'))
            && $context->isAction(RestApiReadInterface::ACTION_LIST);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Context $context)
    {
        if ($context->has('query')) {
            $value = $context->get('query');

            if ($value instanceof QueryBuilder) {
                $countQb = $this->countQueryBuilderOptimizer->getCountQueryBuilder($value);
                $query   = $countQb->getQuery();
            } elseif ($value instanceof Query) {
                $query = clone $value;
                $query->setMaxResults(null)->setFirstResult(null);
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Expected instance of QueryBuilder or Query, "%s" given',
                        is_object($value) ? get_class($value) : gettype($value)
                    )
                );
            }
        } else {
            $qb    = $context->getController()->getManager()->getRepository()->createQueryBuilder('e');
            $query = $qb->getQuery();
        }

        $totalCount = $this->calculateCount($query);
        $context->getResponse()->headers->set(self::HEADER_NAME, $totalCount);
    }

    /**
     * @param Query $query
     *
     * @return int
     */
    protected function calculateCount(Query $query)
    {
        return QueryCountCalculator::calculateCount($query);
    }
}
