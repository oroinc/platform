<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Exception\InvalidMaxNestingLevelException;
use Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;

class MenuUpdateManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var BuilderChainProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $builderChainProvider;

    /** @var MenuUpdateHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $menuUpdateHelper;

    /** @var MenuUpdateManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entityRepository = $this->getMock(EntityRepository::class, [], [], '', false);

        $this->entityManager = $this->getMock(EntityManager::class, [], [], '', false);
        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->with(MenuUpdateStub::class)
            ->will($this->returnValue($this->entityRepository));

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $managerRegistry */
        $managerRegistry = $this->getMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with(MenuUpdateStub::class)
            ->will($this->returnValue($this->entityManager));

        $this->builderChainProvider = $this->getMock(BuilderChainProvider::class, [], [], '', false);
        $this->menuUpdateHelper = $this->getMock(MenuUpdateHelper::class, [], [], '', false);

        $this->manager = new MenuUpdateManager($managerRegistry, $this->builderChainProvider, $this->menuUpdateHelper);
    }

    public function testCreateMenuUpdate()
    {
        $ownershipType = MenuUpdate::OWNERSHIP_USER;
        $ownerId = 1;

        $entity = new MenuUpdateStub();
        $entity
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
        ;

        $this->manager->setEntityClass(MenuUpdateStub::class);
        $result = $this->manager->createMenuUpdate($ownershipType, $ownerId);

        $this->assertEquals($entity, $result);
        $this->assertEquals($entity->getOwnershipType(), $result->getOwnershipType());
        $this->assertEquals($entity->getOwnerId(), $result->getOwnerId());
    }

    public function testUpdateMenuUpdate()
    {
        $menuName = 'test-menu';

        $entity = new MenuUpdateStub();
        $entity->setMenu($menuName);

        $menu = $this->getMock(ItemInterface::class);
        $menu->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($menuName));
        $menu->expects($this->once())
            ->method('getExtra')
            ->with('max_nesting_level', 0)
            ->will($this->returnValue(0));

        $this->builderChainProvider
            ->expects($this->exactly(2))
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($entity);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->with($entity);

        $this->manager->setEntityClass(MenuUpdateStub::class);
        $this->manager->updateMenuUpdate($entity);
    }

    public function testRemoveMenuUpdate()
    {
        $entity = new MenuUpdateStub();

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($entity);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->with($entity);

        $this->manager->setEntityClass(MenuUpdateStub::class);
        $this->manager->removeMenuUpdate($entity);
    }

    public function testGetMenuUpdatesByMenuAndScope()
    {
        $menuName = 'test-menu';
        $ownershipType = MenuUpdate::OWNERSHIP_USER;
        $ownerId = 1;

        $update = new MenuUpdateStub();

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['menu' => $menuName, 'ownershipType' => $ownershipType, 'ownerId' => $ownerId])
            ->will($this->returnValue([$update]));

        $result = $this->manager->getMenuUpdatesByMenuAndScope($menuName, $ownershipType, $ownerId);
        $this->assertEquals([$update], $result);
    }

    public function testGetMenuUpdateByKeyAndScopeDatabase()
    {
        $menuName = 'test-menu';
        $key = 'test-key';
        $ownershipType = MenuUpdate::OWNERSHIP_USER;
        $ownerId = 1;

        $update = new MenuUpdateStub();

        $menu = $this->getMock(ItemInterface::class);

        $item = $this->getMock(ItemInterface::class);

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['menu' => $menuName, 'key' => $key, 'ownershipType' => $ownershipType, 'ownerId' => $ownerId])
            ->will($this->returnValue($update));

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $this->menuUpdateHelper
            ->expects($this->once())
            ->method('findMenuItem')
            ->with($menu, $key)
            ->will($this->returnValue($item));

        $this->menuUpdateHelper
            ->expects($this->once())
            ->method('updateMenuUpdate')
            ->with($update, $item, $menuName);

        $result = $this->manager->getMenuUpdateByKeyAndScope($menuName, $key, $ownershipType, $ownerId);

        $this->assertEquals($update, $result);
    }

    public function testGetMenuUpdateByKeyAndScopeYml()
    {
        $menuName = 'test-menu';
        $key = 'test-key';
        $ownershipType = MenuUpdate::OWNERSHIP_USER;
        $ownerId = 1;

        $update = new MenuUpdateStub();
        $update
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
        ;

        $menu = $this->getMock(ItemInterface::class);

        $item = $this->getMock(ItemInterface::class);

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['menu' => $menuName, 'key' => $key, 'ownershipType' => $ownershipType, 'ownerId' => $ownerId])
            ->will($this->returnValue(null));

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $this->menuUpdateHelper
            ->expects($this->once())
            ->method('findMenuItem')
            ->with($menu, $key)
            ->will($this->returnValue($item));

        $this->menuUpdateHelper
            ->expects($this->once())
            ->method('updateMenuUpdate')
            ->with($update, $item, $menuName);

        $result = $this->manager->getMenuUpdateByKeyAndScope($menuName, $key, $ownershipType, $ownerId);

        $this->assertEquals($update, $result);
    }

    public function testGetMenuUpdateByKeyAndScopeEmpty()
    {
        $menuName = 'test-menu';
        $key = 'test-key';
        $ownershipType = MenuUpdate::OWNERSHIP_USER;
        $ownerId = 1;

        $update = new MenuUpdateStub();
        $update
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
        ;

        $menu = $this->getMock(ItemInterface::class);

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['menu' => $menuName, 'key' => $key, 'ownershipType' => $ownershipType, 'ownerId' => $ownerId])
            ->will($this->returnValue(null));

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $this->menuUpdateHelper
            ->expects($this->once())
            ->method('findMenuItem')
            ->with($menu, $key)
            ->will($this->returnValue(null));

        $result = $this->manager->getMenuUpdateByKeyAndScope($menuName, $key, $ownershipType, $ownerId);

        $this->assertEquals($update, $result);
    }

    public function testReorderMenuUpdate()
    {
        $this->manager->setEntityClass(MenuUpdateStub::class);

        $menuName = 'test-menu';
        $ownershipType = MenuUpdate::OWNERSHIP_USER;
        $ownerId = 1;

        $factory = new MenuFactory();
        $menu = $factory->createItem($menuName);

        $this->builderChainProvider
            ->expects($this->any())
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $item = $menu->addChild('first_menu');

        $orderedChildren = [
            0 => $menu->addChild('first_menu'),
            1 => $menu->addChild('second_menu'),
            2 => $item->addChild('third_menu'),
            3 => $menu->addChild('fourth_menu'),
        ];

        $update1 = new MenuUpdateStub();
        $update1->setKey('second_menu');
        $update1->setPriority(1);

        $update3 = new MenuUpdateStub();
        $update3->setKey('fourth_menu');
        $update3->setPriority(3);

        $updates = [$update1, $update3];

        $this->entityRepository
            ->expects($this->once())
            ->method('findBy')
            ->with([
                'menu' => $menuName,
                'key' => ['first_menu', 'second_menu', 'third_menu', 'fourth_menu'],
                'ownershipType' => $ownershipType,
                'ownerId' => $ownerId,
            ])
            ->will($this->returnValue($updates));

        $update0 = new MenuUpdateStub();
        $update0->setKey('first_menu');
        $update0->setMenu($menuName);
        $update0->setParentKey(null);
        $update0->setPriority(0);
        $update0->setOwnershipType($ownershipType);
        $update0->setOwnerId($ownerId);

        $update2 = new MenuUpdateStub();
        $update2->setKey('third_menu');
        $update2->setMenu($menuName);
        $update2->setParentKey('first_menu');
        $update2->setPriority(2);
        $update2->setOwnershipType($ownershipType);
        $update2->setOwnerId($ownerId);

        $this->entityManager
            ->expects($this->exactly(4))
            ->method('persist')
            ->withConsecutive(
                [$update1],
                [$update3],
                [$update0],
                [$update2]
            );

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->with([$update1, $update3, $update0, $update2]);

        $this->manager->reorderMenuUpdate($menuName, $orderedChildren, $ownershipType, $ownerId);
    }

    public function testGetMenu()
    {
        $menuName = 'test-menu';

        $menu = $this->getMock(ItemInterface::class);

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $this->assertEquals($menu, $this->manager->getMenu($menuName));
    }

    public function testFindMenuItem()
    {
        $menuName = 'test-menu';

        $key = 'test-key';

        $menu = $this->getMock(ItemInterface::class);

        $item = $this->getMock(ItemInterface::class);

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $this->menuUpdateHelper
            ->expects($this->once())
            ->method('findMenuItem')
            ->with($menu, $key)
            ->will($this->returnValue($item));

        $this->assertEquals($item, $this->manager->findMenuItem($menuName, $key));
    }

    /**
     * @dataProvider maxNestingLevelProvider
     *
     * @param int $level
     * @param int $maxLevel
     * @param bool $hasException
     */
    public function testCheckMaxNestingLevel($level, $maxLevel, $hasException)
    {
        $menuName = 'test-menu';

        $key = 'test-key';
        $parentKey = 'test-key';

        $menu = $this->getMock(ItemInterface::class);
        $menu->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($menuName));
        $menu->expects($this->once())
            ->method('getExtra')
            ->with('max_nesting_level', 0)
            ->will($this->returnValue($maxLevel));

        $factory = new MenuFactory();
        $item = $factory->createItem($key);
        $startItem = $item;

        $items = [$item];
        $range = range(1, $level);
        array_shift($range);
        foreach ($range as $value) {
            $item->setParent($factory->createItem($value));
            $item = $item->getParent();
            $items[] = $item;
        }

        $this->builderChainProvider
            ->expects($this->exactly(2))
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $this->menuUpdateHelper
            ->expects($this->once())
            ->method('findMenuItem')
            ->with($menu, $key)
            ->will($this->returnValue($startItem));

        $update0 = new MenuUpdateStub();
        $update0->setParentKey($parentKey);
        $update0->setMenu($menuName);
        $update0->setDefaultTitle('default-title');

        if ($hasException) {
            $this->setExpectedException(InvalidMaxNestingLevelException::class, sprintf(
                "Item \"%s\" can't be saved. Max nesting level for menu \"%s\" is %d.",
                'default-title',
                $menuName,
                $maxLevel
            ));
        }

        $this->manager->checkMaxNestingLevel($update0);
    }

    /**
     * @return array
     */
    public function maxNestingLevelProvider()
    {
        return [
            [1, 0, false],
            [2, 0, false],
            [3, 0, false],
            [1, 1, false],
            [2, 1, true],
            [3, 1, true],
            [1, 2, false],
            [2, 2, false],
            [3, 2, true],
            [1, 3, false],
            [2, 3, false],
            [3, 3, false],
        ];
    }
}
