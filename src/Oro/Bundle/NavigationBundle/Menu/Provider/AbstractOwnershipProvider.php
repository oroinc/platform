<?php

namespace Oro\Bundle\NavigationBundle\Menu\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

abstract class AbstractOwnershipProvider implements OwnershipProviderInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $entityClass;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string          $entityClass
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        $entityClass
    ) {
        $this->registry = $managerRegistry;
        $this->entityClass = $entityClass;
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
        $repository = $this->registry->getManagerForClass($this->entityClass)->getRepository($this->entityClass);

        return $repository->findBy(
            [
                'menu' => $menuName,
                'ownershipType' => $this->getType(),
                'ownerId' => $this->getId()
            ]
        );
    }
}
