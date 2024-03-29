<?php

namespace Oro\Bundle\ReportBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ReportBundle\Entity\ReportType;

/**
 * Provides report types choices
 */
class ReportManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Get report types
     *
     * @return array
     *  key => report name
     *  value => report label
     */
    public function getReportTypeChoices()
    {
        $result = [];
        $types = $this->em->getRepository(ReportType::class)->findAll();
        foreach ($types as $type) {
            $result[$type->getLabel()] = $type->getName();
        }

        return $result;
    }
}
