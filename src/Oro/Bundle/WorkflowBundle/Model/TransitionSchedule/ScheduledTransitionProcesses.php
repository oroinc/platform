<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Component\DoctrineUtils\ORM\LikeQueryHelperTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ScheduledTransitionProcesses
{
    use LikeQueryHelperTrait;

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
     * @param ScheduledTransitionProcessName $scheduledTransitionProcessName
     * @return ProcessDefinition|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function get(ScheduledTransitionProcessName $scheduledTransitionProcessName)
    {
        $qb = $this->getEntityRepository()->createQueryBuilder('e')->where('e.name = :name');
        $qb->setParameter(
            'name',
            $scheduledTransitionProcessName->getName()
        );

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $workflow
     * @return array|ProcessDefinition[]
     */
    public function workflowRelated($workflow)
    {
        $qb = $this->getEntityRepository()->createQueryBuilder('p');
        $qb = $qb->where($qb->expr()->like('p.name', ":match ESCAPE '!'"));

        $matchWorkflowRelated = implode(
            ScheduledTransitionProcessName::DELIMITER,
            [ScheduledTransitionProcessName::IDENTITY_PREFIX, $workflow]
        );

        $qb->setParameter('match', $this->makeLikeParam($matchWorkflowRelated, '%s%%'));

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array|ProcessDefinition[]
     */
    public function all()
    {
        $allMatch = ScheduledTransitionProcessName::IDENTITY_PREFIX . ScheduledTransitionProcessName::DELIMITER;

        $qb = $this->getEntityRepository()->createQueryBuilder('p');
        $qb = $qb->where($qb->expr()->like('p.name', ":match ESCAPE '!'"));

        $qb->setParameter('match', $this->makeLikeParam($allMatch, '%s%%'));

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
