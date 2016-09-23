<?php

namespace Oro\Bundle\NavigationBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Knp\Menu\ItemInterface;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;

class MenuUpdateManager
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var BuilderChainProvider */
    private $builderChainProvider;

    /** @var MenuUpdateHelper */
    private $menuUpdateHelper;

    /** @var string */
    private $entityClass;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param BuilderChainProvider $builderChainProvider
     * @param MenuUpdateHelper $menuUpdateHelper
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        BuilderChainProvider $builderChainProvider,
        MenuUpdateHelper $menuUpdateHelper
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->builderChainProvider = $builderChainProvider;
        $this->menuUpdateHelper = $menuUpdateHelper;
    }

    /**
     * @param string $entityClass
     *
     * @return MenuUpdateManager
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @param int $ownershipType
     * @param int $ownerId
     *
     * @return MenuUpdateInterface
     */
    public function createMenuUpdate($ownershipType, $ownerId)
    {
        /** @var MenuUpdateInterface $entity */
        $entity = new $this->entityClass;
        $entity
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
        ;

        return $entity;
    }

    /**
     * @param MenuUpdateInterface $update
     */
    public function updateMenuUpdate(MenuUpdateInterface $update)
    {
        $this->getEntityManager()->persist($update);
        $this->getEntityManager()->flush($update);
    }

    /**
     * @param MenuUpdateInterface $update
     */
    public function removeMenuUpdate(MenuUpdateInterface $update)
    {
        $this->getEntityManager()->remove($update);
        $this->getEntityManager()->flush($update);
    }

    /**
     * @param string $menuName
     * @param string $key
     * @param int $ownershipType
     * @param int $ownerId
     *
     * @return null|MenuUpdateInterface
     */
    public function getMenuUpdateByKeyAndScope($menuName, $key, $ownershipType, $ownerId)
    {
        /** @var MenuUpdateInterface $update */
        $update = $this->getRepository()->findOneBy([
            'menu' => $menuName,
            'key' => $key,
            'ownershipType' => $ownershipType,
            'ownerId' => $ownerId,
        ]);
        
        if (!$update) {
            $update = $this->createMenuUpdate($ownershipType, $ownerId);
        }

        return $this->getMenuUpdateFromMenu($update, $menuName, $key);
    }

    /**
     * @param MenuUpdateInterface $update
     * @param string $menuName
     * @param string $key
     *
     * @return MenuUpdateInterface
     */
    private function getMenuUpdateFromMenu(MenuUpdateInterface $update, $menuName, $key)
    {
        $menu = $this->getMenu($menuName);
        $item = $this->menuUpdateHelper->findMenuItem($menu, $key);

        if ($item) {
            $this->menuUpdateHelper->updateMenuUpdate($update, $item, $menu->getName());
        }

        return $update;
    }

    /**
     * @param string $name
     *
     * @return ItemInterface
     */
    public function getMenu($name)
    {
        return $this->builderChainProvider->get($name);
    }

    /**
     * @return EntityRepository
     */
    private function getRepository()
    {
        return $this->getEntityManager()->getRepository($this->entityClass);
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass($this->entityClass);
    }
}
