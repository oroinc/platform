<?php

namespace Oro\Bundle\NavigationBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Knp\Menu\ItemInterface;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\JsTree\MenuUpdateTreeHandler;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

class MenuUpdateManager
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var BuilderChainProvider */
    private $builderChainProvider;

    /** @var string */
    private $entityClass;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param BuilderChainProvider $builderChainProvider
     */
    public function __construct(ManagerRegistry $managerRegistry, BuilderChainProvider $builderChainProvider)
    {
        $this->managerRegistry = $managerRegistry;
        $this->builderChainProvider = $builderChainProvider;
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
     * Create menu update entity
     *
     * @param int $ownershipType
     * @param int $ownerId
     * @param array $options
     *
     * @return MenuUpdateInterface
     */
    public function createMenuUpdate($ownershipType, $ownerId, array $options = [])
    {
        /** @var MenuUpdateInterface $entity */
        $entity = new $this->entityClass;
        $entity
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId);
        if (isset($options['key'])) {
            $entity->setKey($options['key']);
        } else {
            $entity->setKey($this->generateKey());
        }
        $isCustom = isset($options['custom']) && $options['custom'];
        $entity->setCustom($isCustom);
        if (isset($options['parentKey'])) {
            $parent = $this->getMenuUpdateByKeyAndScope(
                $options['menu'],
                $options['parentKey'],
                $ownershipType,
                $ownerId
            );
            if($parent) {
                $entity->setParentKey($options['parentKey']);
            }
            // todo consider to create not found exception
        }

        $entity->setMenu($options['menu']);

        if (isset($options['isDivider']) && $options['isDivider']) {
            $entity->setDivider(true);
            $entity->setDefaultTitle(MenuUpdateTreeHandler::MENU_ITEM_DIVIDER_LABEL);
            $entity->setUri('#');
        }

        return $entity;
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
     * Get existing or create new MenuUpdate for specified menu, key and scope
     *
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
            $update = $this->createMenuUpdate($ownershipType, $ownerId, ['key' => $key, 'menu' => $menuName]);
        }

        return $this->getMenuUpdateFromMenu($update, $menuName, $key, $ownershipType);
    }

    /**
     * Get list of menu update with new position
     *
     * @param string $menuName
     * @param ItemInterface[] $orderedChildren
     * @param int $ownershipType
     * @param int $ownerId
     *
     * @return MenuUpdateInterface[]
     */
    public function getReorderedMenuUpdates($menuName, $orderedChildren, $ownershipType, $ownerId)
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
            unset($orderedChildren[$order[$update->getKey()]]);
        }

        foreach ($orderedChildren as $priority => $child) {
            $update = $this->createMenuUpdate($ownershipType, $ownerId, $child->getName());
            MenuUpdateUtils::updateMenuUpdate($update, $child, $menuName);
            $update->setPriority($priority);
            $updates[] = $update;
        }
        
        return $updates;
    }

    /**
     * @param string $menuName
     * @param string $key
     * @param string $ownershipType
     * @param int $ownerId
     */
    public function showMenuItem($menuName, $key, $ownershipType, $ownerId)
    {
        $item = MenuUpdateUtils::findMenuItem($this->getMenu($menuName), $key);
        if ($item !== null) {
            $update = $this->getMenuUpdateByKeyAndScope($menuName, $item->getName(), $ownershipType, $ownerId);
            $update->setActive(true);
            $this->getEntityManager()->persist($update);

            $this->showMenuItemParents($menuName, $item, $ownershipType, $ownerId);
            $this->showMenuItemChildren($menuName, $item, $ownershipType, $ownerId);

            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string $menuName
     * @param ItemInterface $item
     * @param string $ownershipType
     * @param int $ownerId
     */
    private function showMenuItemParents($menuName, $item, $ownershipType, $ownerId)
    {
        $parent = $item->getParent();
        if ($parent !== null && !$parent->isDisplayed()) {
            $update = $this->getMenuUpdateByKeyAndScope($menuName, $parent->getName(), $ownershipType, $ownerId);
            $update->setActive(true);
            $this->getEntityManager()->persist($update);

            $this->showMenuItemParents($menuName, $parent, $ownershipType, $ownerId);
        }
    }

    /**
     * @param string $menuName
     * @param ItemInterface $item
     * @param string $ownershipType
     * @param int $ownerId
     */
    private function showMenuItemChildren($menuName, $item, $ownershipType, $ownerId)
    {
        /** @var ItemInterface $child */
        foreach ($item->getChildren() as $child) {
            $update = $this->getMenuUpdateByKeyAndScope($menuName, $child->getName(), $ownershipType, $ownerId);
            $update->setActive(true);
            $this->getEntityManager()->persist($update);

            $this->showMenuItemChildren($menuName, $child, $ownershipType, $ownerId);
        }
    }

    /**
     * @param string $menuName
     * @param string $key
     * @param string $ownershipType
     * @param int $ownerId
     */
    public function hideMenuItem($menuName, $key, $ownershipType, $ownerId)
    {
        $item = MenuUpdateUtils::findMenuItem($this->getMenu($menuName), $key);
        if ($item !== null) {
            $update = $this->getMenuUpdateByKeyAndScope($menuName, $item->getName(), $ownershipType, $ownerId);
            $update->setActive(false);
            $this->getEntityManager()->persist($update);

            $this->hideMenuItemChildren($menuName, $item, $ownershipType, $ownerId);

            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string $menuName
     * @param ItemInterface $item
     * @param string $ownershipType
     * @param int $ownerId
     */
    private function hideMenuItemChildren($menuName, $item, $ownershipType, $ownerId)
    {
        /** @var ItemInterface $child */
        foreach ($item->getChildren() as $child) {
            $update = $this->getMenuUpdateByKeyAndScope($menuName, $child->getName(), $ownershipType, $ownerId);
            $update->setActive(false);
            $this->getEntityManager()->persist($update);

            $this->hideMenuItemChildren($menuName, $child, $ownershipType, $ownerId);
        }
    }

    /**
     * Get menu built by BuilderChainProvider
     *
     * @param string $name
     * @param array $options
     *
     * @return ItemInterface
     */
    public function getMenu($name, $options = [])
    {
        $options = array_merge($options, [
            'ignoreCache' => true
        ]);

        return $this->builderChainProvider->get($name, $options);
    }

    /**
     * @param string $menuName
     * @param string $key
     * @param int $ownershipType
     *
     * @return ItemInterface|null
     */
    public function findMenuItem($menuName, $key, $ownershipType)
    {
        $options = [
            'ignoreCache' => true,
            'ownershipType' => $ownershipType
        ];
        $menu = $this->getMenu($menuName, $options);

        return MenuUpdateUtils::findMenuItem($menu, $key);
    }

    /**
     * @param MenuUpdateInterface $update
     * @param string $menuName
     * @param string $key
     * @param int $ownershipType
     *
     * @return MenuUpdateInterface
     */
    private function getMenuUpdateFromMenu(MenuUpdateInterface $update, $menuName, $key, $ownershipType)
    {
        $item = $this->findMenuItem($menuName, $key, $ownershipType);

        if ($item) {
            MenuUpdateUtils::updateMenuUpdate($update, $item, $menuName);
        } else {
            $update->setCustom(true);
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

    /**
     * Reset menu updates depending on ownership type and owner id
     *
     * @param int    $ownershipType
     * @param int    $ownerId
     * @param string $menu
     */
    public function resetMenuUpdatesWithOwnershipType($ownershipType, $ownerId = null, $menu = null)
    {
        $criteria = ['ownershipType' => $ownershipType];

        if ($ownerId) {
            $criteria['ownerId'] = $ownerId;
        }

        if ($menu) {
            $criteria['menu'] = $menu;
        }

        $menuUpdates = $this->getRepository()->findBy($criteria);

        foreach ($menuUpdates as $menuUpdate) {
            $this->getEntityManager()->remove($menuUpdate);
        }

        $this->getEntityManager()->flush($menuUpdates);
    }
}
