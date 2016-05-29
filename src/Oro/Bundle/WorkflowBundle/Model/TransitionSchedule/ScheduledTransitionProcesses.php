<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ScheduledTransitionProcesses
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var string
     */
    private $processEntityClass;

    /**
     * @param ManagerRegistry $registry
     * @param string $processDefinitionEntityClass
     */
    public function __construct(ManagerRegistry $registry, $processDefinitionEntityClass)
    {
        $this->registry = $registry;
        $this->processEntityClass = $processDefinitionEntityClass;
    }

    /**
     * @param $workflow
     * @param $transition
     * @return ProcessDefinition|null
     */
    public function exact($workflow, $transition)
    {
        $qb = $this->getEntityRepository()->createQueryBuilder('e')->where('e.name = :name');
        $qb->setParameter(
            'name',
            (string)(new ScheduledTransitionProcessName($workflow, $transition))
        );

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @param string $workflow
     * @return array|ProcessDefinition
     */
    public function workflowRelated($workflow)
    {
        $qb = $this->getEntityRepository()->createQueryBuilder('p');
        $qb = $qb->where($qb->expr()->like('p.name', ':suffix'));

        $qb->setParameter(
            'suffix',
            implode(
                ScheduledTransitionProcessName::DELIMITER,
                [$workflow, '%', ScheduledTransitionProcessName::IDENTITY_SUFFIX]
            )
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array|ProcessDefinition[]
     */
    public function all()
    {
        $qb = $this->getEntityRepository()->createQueryBuilder('p');
        $qb = $qb->where($qb->expr()->like('p.name', ':suffix'));

        //todo escape underlines
        $qb->setParameter(
            'suffix',
            '%' . ScheduledTransitionProcessName::DELIMITER . ScheduledTransitionProcessName::IDENTITY_SUFFIX
        );



        return $qb->getQuery()->getResult();
    }

    /**
     * @return EntityRepository
     */
    private function getEntityRepository()
    {
        return $this->registry->getManagerForClass($this->processEntityClass)->getRepository($this->processEntityClass);
    }
}
