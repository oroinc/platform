<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestApiReadInterface;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;

class TotalHeaderHandler implements IncludeHandlerInterface
{
    const HEADER_NAME = 'X-Total-Count';

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
    public function supports($object, array $context)
    {
        return $object instanceof RestApiReadInterface && $object instanceof EntityManagerAwareInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($object, array $context, Request $request, Response $response)
    {
        /** @var RestApiReadInterface|EntityManagerAwareInterface $object */
        if (isset($context['query'])) {
            $value = $context['query'];

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
            $qb    = $object->getManager()->getRepository()->createQueryBuilder('e');
            $query = $qb->getQuery();
        }

        $totalCount = QueryCountCalculator::calculateCount($query);
        $response->headers->set(self::HEADER_NAME, $totalCount);
    }
}
