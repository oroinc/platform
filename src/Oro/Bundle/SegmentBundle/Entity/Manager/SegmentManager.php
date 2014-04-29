<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentManager
{
    const PER_PAGE = 20;

    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Get segment types choice list
     *
     * @return array [
     *  key   => segment type name
     *  value => segment type label
     * ]
     */
    public function getSegmentTypeChoices()
    {
        $result = [];
        $types  = $this->em->getRepository('OroSegmentBundle:SegmentType')->findAll();
        foreach ($types as $type) {
            $result[$type->getName()] = $type->getLabel();
        }

        return $result;
    }

    /**
     * @param string $entityName
     * @param string $term
     * @param integer $page optional
     *
     * @return array
     */
    public function getSegmentByEntityName($entityName, $term, $page = 1)
    {
        $offset = is_numeric($page) && $page > 1 ? ($page - 1) * SegmentManager::PER_PAGE : 0;
        $result = array(
            'items' => array(),
        );

        $queryBuilder = $this->em->getRepository("OroSegmentBundle:Segment")
            ->createQueryBuilder('segment')
            ->where('segment.entity = :entity')
            ->setParameter('entity', $entityName);

        if (!empty($term)) {
            $queryBuilder
                ->andWhere('segment.name LIKE :segmentName')
                ->setParameter('segmentName', sprintf('%%%s%%', $term));
        }

        $segments = $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getResult();

        /** @var Segment $segment */
        foreach ($segments as $segment) {
            $result['items'][] = array(
                'id'   => 'segment_' . $segment->getId(),
                'text' => $segment->getName(),
                'type' => 'segment',
            );
        }

        return $result;
    }
}
