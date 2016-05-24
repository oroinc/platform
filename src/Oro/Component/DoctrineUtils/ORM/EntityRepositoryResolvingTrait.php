<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\ORM\EntityRepository;

trait EntityRepositoryResolvingTrait
{
    use EntityManagerResolvingTrait;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        if (null !== $this->repository) {
            return $this->repository;
        }

        return $this->repository = $this->getObjectManager()->getRepository($this->getEntityClass());
    }
}
