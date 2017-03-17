<?php

namespace Oro\Bundle\SegmentBundle\Layout\DataProvider;

use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryBuilderRegistry;

class SegmentProvider
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var SegmentQueryBuilderRegistry */
    private $segmentQueryBuilderRegistry;

    /**
     * @param DoctrineHelper              $doctrineHelper
     * @param SegmentQueryBuilderRegistry $segmentQueryBuilderRegistry
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SegmentQueryBuilderRegistry $segmentQueryBuilderRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->segmentQueryBuilderRegistry = $segmentQueryBuilderRegistry;
    }

    /**
     * @param int $segmentId
     *
     * @return array
     * // TODO Get segment id from configuration in BB-7975. Remove default value
     */
    public function getCollection($segmentId = 1)
    {
        /** @var Segment $segment */
        $segment = $this->doctrineHelper->getEntityRepository(Segment::class)->find($segmentId);
        if ($segment !== null) {
            $segmentQueryBuilder = $this->segmentQueryBuilderRegistry->getQueryBuilder($segment->getType()->getName());
            if ($segmentQueryBuilder !== null) {
                $queryBuilder = $segmentQueryBuilder->getQueryBuilder($segment);
                $queryBuilder->setMaxResults($segment->getRecordsLimit());

                $repository = $this->doctrineHelper->getEntityRepository($segment->getEntity());

                $qb = $repository->createQueryBuilder('u');
                $qb->where($qb->expr()->in('u.id', $queryBuilder->getDQL()));

                /** @var Parameter[] $params */
                $params = $queryBuilder->getParameters();
                foreach ($params as $param) {
                    $qb->setParameter($param->getName(), $param->getValue(), $param->getType());
                }

                return $qb->getQuery()->getResult();
            }
        }

        return [];
    }
}
