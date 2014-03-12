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
}
