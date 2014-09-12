<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

/**
 * @deprecated since 1.4. Will be removed in 2.0
 */
class OptionSetRepository extends EntityRepository
{
    /**
     * @param $fieldConfigId
     * @return object
     */
    public function findOptionsByField($fieldConfigId)
    {
        return $this->findBy(
            ['field' => $fieldConfigId],
            ['priority' => Criteria::ASC]
        );
    }
}
