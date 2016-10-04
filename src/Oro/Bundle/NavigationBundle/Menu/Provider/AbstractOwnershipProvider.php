<?php

namespace Oro\Bundle\NavigationBundle\Menu\Provider;

use Doctrine\ORM\EntityRepository;

abstract class AbstractOwnershipProvider implements OwnershipProviderInterface
{
    /** @var EntityRepository */
    protected $repository;

    /**
     * @param EntityRepository $repository
     */
    public function __construct(
        EntityRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function getType();

    /**
     * {@inheritDoc}
     */
    abstract public function getId();

    /**
     * {@inheritDoc}
     */
    public function getMenuUpdates($menuName)
    {
        return $this->repository->getMenuUpdates($menuName, $this->getType(), $this->getId());
    }
}
