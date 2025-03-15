<?php

namespace Oro\Bundle\ReportBundle\Entity\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ReportBundle\Entity\ReportType;

/**
 * Provides report types choices.
 */
class ReportManager
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    /**
     * @return array [report name => report label]
     */
    public function getReportTypeChoices(): array
    {
        $result = [];
        $types = $this->doctrine->getRepository(ReportType::class)->findAll();
        foreach ($types as $type) {
            $result[$type->getLabel()] = $type->getName();
        }

        return $result;
    }
}
