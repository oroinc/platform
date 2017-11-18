<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ScopeEntityStorage
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var array
     */
    private $scheduledForInsert = [];

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Scope $scope
     * @param ScopeCriteria $criteria
     */
    public function scheduleForInsert(Scope $scope, ScopeCriteria $criteria)
    {
        $this->scheduledForInsert[$this->getScopeCriteriaCacheKey($criteria)] = $scope;
    }

    /**
     * @param ScopeCriteria $criteria
     * @return Scope|null
     */
    public function getScheduledForInsertByCriteria(ScopeCriteria $criteria)
    {
        $criteriaKey = $this->getScopeCriteriaCacheKey($criteria);
        if (array_key_exists($criteriaKey, $this->scheduledForInsert)) {
            return $this->scheduledForInsert[$criteriaKey];
        }

        return null;
    }

    public function persistScheduledForInsert()
    {
        if (!$this->scheduledForInsert) {
            return;
        }

        $em = $this->getEntityManager();
        foreach ($this->scheduledForInsert as $scope) {
            $em->persist($scope);
        }
    }

    public function flush()
    {
        if (!$this->scheduledForInsert) {
            return;
        }

        $this->getEntityManager()->flush(array_values($this->scheduledForInsert));
    }

    public function clear()
    {
        $this->scheduledForInsert = [];
    }

    /**
     * @return ScopeRepository
     */
    public function getRepository()
    {
        return $this->getEntityManager()->getRepository(Scope::class);
    }

    /**
     * @param ScopeCriteria $criteria
     * @return string
     */
    protected function getScopeCriteriaCacheKey(ScopeCriteria $criteria)
    {
        return serialize($criteria);
    }

    /**
     * @return EntityManagerInterface|null
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass(Scope::class);
    }
}
