<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

class SegmentManager
{
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
        $types = $this->em->getRepository('OroSegmentBundle:SegmentType')->findAll();
        foreach ($types as $type) {
            $result[$type->getName()] = $type->getLabel();
        }

        return $result;
    }

    /**
     * @param string $entityName
     * @param string $term
     *
     * @return array
     */
    public function getSegmentByEntityName($entityName, $term)
    {
        $result = [];

        if (!empty($term)) {
            $segments = $this->em->getRepository("OroSegmentBundle:Segment")->createQueryBuilder('s')
                ->where('s.entity = :entity')
                ->andWhere('s.name LIKE :segmentName')
                ->setParameter('entity', $entityName)
                ->setParameter('segmentName', sprintf('%%%s%%', $term))
                ->setMaxResults(20)
                ->getQuery()
                ->getResult();

            foreach ($segments as $segment) {
                $result[] = [
                    'id'   => 'segment_'.$segment->getId(),
                    'text' => $segment->getName(),
                    'type' => 'segment',
                ];
            }
        }

        return $result;
    }
}
