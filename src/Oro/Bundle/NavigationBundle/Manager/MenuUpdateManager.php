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
            ->setKey($this->generateKey())
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
     * @param int $ownershipType
     * @param int $ownerId
     *
     * @return MenuUpdateInterface[]
     */
    public function getMenuUpdatesByMenuAndScope($menuName, $ownershipType, $ownerId)
    {
        return $this->getRepository()->findBy([
            'menu' => $menuName,
            'ownershipType' => $ownershipType,
            'ownerId' => $ownerId,
        ]);
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
     * @param string $menuName
     * @param ItemInterface[] $orderedChildren
     * @param int $ownershipType
     * @param int $ownerId
     */
    public function reorderMenuUpdate($menuName, $orderedChildren, $ownershipType, $ownerId)
    {
        $order = [];
        foreach ($orderedChildren as $priority => $child) {
            $order[$child->getName()] = $priority;
        }
        
        /** @var MenuUpdateInterface[] $updates */
        $updates = $this->getRepository()->findBy([
            'menu' => $menuName,
            'key' => array_keys($order),
            'ownershipType' => $ownershipType,
            'ownerId' => $ownerId,
        ]);
        
        foreach ($updates as $update) {
            $update->setPriority($order[$update->getKey()]);
            $this->getEntityManager()->persist($update);

            unset($orderedChildren[$order[$update->getKey()]]);
        }

        foreach ($orderedChildren as $priority => $child) {
            $update = $this->createMenuUpdate($ownershipType, $ownerId);
            $update->setKey($child->getName());
            $update->setMenu($menuName);
            $parentKey = $child->getParent()->getName();
            $update->setParentKey($parentKey != $menuName ? $parentKey : null);
            $update->setPriority($priority);

            $this->getEntityManager()->persist($update);
            $updates[] = $update;
        }

        $this->getEntityManager()->flush($updates);
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
     * @param string $menuName
     * @param string $key
     * @return ItemInterface|null
     */
    public function findMenuItem($menuName, $key)
    {
        $menu = $this->getMenu($menuName);

        return $this->menuUpdateHelper->findMenuItem($menu, $key);
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
        $item = $this->findMenuItem($menuName, $key);

        if ($item) {
            $this->menuUpdateHelper->updateMenuUpdate($update, $item, $menuName);
        }

        return $update;
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

    /**
     * @return string
     */
    public function generateKey()
    {
        return uniqid('menu_item_');
    }
}
