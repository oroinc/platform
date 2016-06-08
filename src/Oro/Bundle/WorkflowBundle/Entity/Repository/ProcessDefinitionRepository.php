<?php
/**
 * Created by PhpStorm.
 * User: Matey
 * Date: 08.06.2016
 * Time: 15:57
 */

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

class ProcessDefinitionRepository extends EntityRepository
{
    /**
     * @param string $like
     * @param string $escapeChar
     * @return array|ProcessDefinition[]
     */
    public function findLikeName($like, $escapeChar = '!')
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where($qb->expr()->like('p.name', ":nameLike ESCAPE '$escapeChar'"));

        $qb->setParameter('nameLike', $like);

        return $qb->getQuery()->getResult();
    }
}