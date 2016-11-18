<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuItemStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class MenuUpdateManagerTest extends \PHPUnit_Framework_TestCase
{
    const MENU_ID = 'menu';
    const SCOPE_TYPE = 'scope_type';

    use MenuItemTestTrait;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var BuilderChainProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $builderChainProvider;

    /** @var  MenuUpdateHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $menuUpdateHelper;

    /** @var  ScopeManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $scopeManager;

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
        $this->scopeManager = $this->getMock(ScopeManager::class, [], [], '', false);

        $this->manager = new MenuUpdateManager(
            $managerRegistry,
            $this->builderChainProvider,
            $this->menuUpdateHelper,
            $this->scopeManager
        );
    }

    public function testCreateMenuUpdate()
    {
        $entity = new MenuUpdateStub();

        $this->manager->setScopeType($this::SCOPE_TYPE);
        $context = ['scopeAttribute' => new \stdClass()];
        $scope = new Scope();

        $this->scopeManager->expects($this::once())
            ->method('find')
            ->with($this::SCOPE_TYPE, $context)
            ->willReturn($scope);

        $entity
            ->setScope($scope)
            ->setMenu(self::MENU_ID)
        ;

        $this->manager->setEntityClass(MenuUpdateStub::class);
        $result = $this->manager->createMenuUpdate($context, ['menu'=> self::MENU_ID]);
        $entity->setKey($result->getKey());

        $this->assertEquals($entity, $result);

        $this->assertEquals($entity->getScope(), $result->getScope());
    }

    /**
     * @expectedException \Oro\Bundle\NavigationBundle\Exception\NotFoundParentException
     * @expectedExceptionMessage Parent with "parent_key" id not found.
     */
    public function testCreateMenuUpdateForNotExistingParent()
    {
        $menu = new MenuItemStub();

        $this->manager->setScopeType($this::SCOPE_TYPE);
        $context = ['scopeAttribute' => new \stdClass()];
        $scope = new Scope();

        $this->scopeManager->expects($this::once())
            ->method('find')
            ->with($this::SCOPE_TYPE, $context)
            ->willReturn($scope);


        $this->builderChainProvider->expects($this->any())
            ->method('get')
            ->with(self::MENU_ID)
            ->willReturn($menu);
        $this->manager->setEntityClass(MenuUpdateStub::class);
        $this->manager->createMenuUpdate($context, ['menu' => self::MENU_ID, 'parentKey' => 'parent_key']);
    }

    public function testGetMenuUpdatesByMenuAndScope()
    {
        $menuName = 'test-menu';

        $update = new MenuUpdateStub();
        $scope = new Scope();

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['menu' => $menuName, 'scopeId' => $scope])
            ->will($this->returnValue([$update]));

        $result = $this->manager->getMenuUpdatesByMenuAndScope($menuName, $scope);
        $this->assertEquals([$update], $result);
    }

    public function testGetMenuUpdateByKeyAndScopeWithMenuItem()
    {
        $key = 'item-1-1-1';
        $scope = new Scope();
        $this->manager->setScopeType($this::SCOPE_TYPE);

        $update = new MenuUpdateStub();
        $update
            ->setScope($scope)
            ->setKey($key)
            ->setCustom(false)
        ;

        $this->scopeManager->expects($this::once())
            ->method('find')
            ->with($this::SCOPE_TYPE, null)
            ->willReturn($scope);

        $menu = $this->getMenu();

        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $item->setUri('uri');

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['menu' => 'menu', 'key' => $key, 'scopeId' => $scope])
            ->will($this->returnValue(null));

        $this->builderChainProvider
            ->expects($this->any())
            ->method('get')
            ->with(self::MENU_ID)
            ->will($this->returnValue($menu));

        $result = $this->manager->getMenuUpdateByKeyAndScope('menu', $key, $scope);

        $update
            ->setDefaultTitle('item-1-1-1')
            ->setParentKey('item-1-1')
            ->setMenu('menu')
            ->setUri('uri');

        $this->assertEquals($update, $result);
    }

    public function testGetMenuUpdateByKeyAndScopeWithoutMenuItem()
    {
        $key = 'item-1-1-1-1';
        $scope = new Scope();
        $this->manager->setScopeType($this::SCOPE_TYPE);

        $update = new MenuUpdateStub();
        $update
            ->setScope($scope)
            ->setKey($key)
            ->setCustom(true)
            ->setMenu(self::MENU_ID)
        ;

        $this->scopeManager->expects($this::once())
            ->method('find')
            ->with($this::SCOPE_TYPE, null)
            ->willReturn($scope);

        $menu = $this->getMenu();

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['menu' => self::MENU_ID, 'key' => $key, 'scopeId' => $scope])
            ->will($this->returnValue(null));

        $this->builderChainProvider
            ->expects($this->any())
            ->method('get')
            ->with(self::MENU_ID)
            ->will($this->returnValue($menu));

        $result = $this->manager->getMenuUpdateByKeyAndScope('menu', $key, $scope);

        $this->assertEquals($update, $result);
    }

    public function testGetReorderedMenuUpdates()
    {
        $this->manager->setEntityClass(MenuUpdateStub::class);

        $scope = new Scope();
        $this->manager->setScopeType($this::SCOPE_TYPE);

        $this->scopeManager->expects($this::exactly(2))
            ->method('find')
            ->with($this::SCOPE_TYPE, null)
            ->willReturn($scope);

        $menu = $this->getMenu();

        $this->builderChainProvider
            ->expects($this->any())
            ->method('get')
            ->with(self::MENU_ID)
            ->will($this->returnValue($menu));

        $update1 = new MenuUpdateStub();
        $update1->setKey('item-2');
        $update1->setPriority(1);

        $update3 = new MenuUpdateStub();
        $update3->setKey('item-4');
        $update3->setPriority(3);

        $updates = [$update1, $update3];

        $this->entityRepository
            ->expects($this->once())
            ->method('findBy')
            ->with([
                'menu' => 'menu',
                'key' => ['item-1', 'item-2', 'item-3', 'item-4'],
                'scopeId' => $scope,
            ])
            ->will($this->returnValue($updates));

        $update0 = new MenuUpdateStub();
        $update0->setKey('item-1');
        $update0->setDefaultTitle('item-1');
        $update0->setMenu('menu');
        $update0->setParentKey(null);
        $update0->setPriority(0);
        $update0->setScope($scope);

        $update2 = new MenuUpdateStub();
        $update2->setKey('item-3');
        $update2->setDefaultTitle('item-3');
        $update2->setMenu('menu');
        $update0->setParentKey(null);
        $update2->setPriority(2);
        $update2->setScope($scope);

        $orderedChildren = array_values($menu->getChildren());
        $this->assertEquals(
            [$update1, $update3, $update0, $update2],
            $this->manager->getReorderedMenuUpdates('menu', $orderedChildren, $scope)
        );
    }

    public function testShowMenuItem()
    {
        $menuName = 'menu';
        $scope = new Scope();
        $this->manager->setScopeType($this::SCOPE_TYPE);

        $this->scopeManager->expects($this::exactly(3))
            ->method('find')
            ->with($this::SCOPE_TYPE, null)
            ->willReturn($scope);

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $menu = $this->getMenu();
        $menu->getChild('item-1')->setDisplay(false);
        $menu->getChild('item-1')->getChild('item-1-1')->setDisplay(false);
        $menu->getChild('item-1')->getChild('item-1-1')->getChild('item-1-1-1')->setDisplay(false);

        $this->builderChainProvider
            ->expects($this->any())
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $update1 = new MenuUpdateStub();
        $update1
            ->setMenu($menuName)
            ->setScope($scope)
            ->setKey('item-1')
            ->setParentKey(null)
            ->setCustom(false)
            ->setActive(true)
            ->setDefaultTitle('item-1')
        ;

        $update11 = new MenuUpdateStub();
        $update11
            ->setMenu($menuName)
            ->setScope($scope)
            ->setKey('item-1-1')
            ->setParentKey('item-1')
            ->setCustom(false)
            ->setActive(true)
            ->setDefaultTitle('item-1-1')
        ;

        $update111 = new MenuUpdateStub();
        $update111
            ->setMenu($menuName)
            ->setScope($scope)
            ->setKey('item-1-1-1')
            ->setParentKey('item-1-1')
            ->setCustom(false)
            ->setActive(true)
            ->setDefaultTitle('item-1-1-1')
        ;

        $this->entityManager->expects($this->exactly(3))
            ->method('persist')
            ->with($this->logicalOr(
                $this->equalTo($update1),
                $this->equalTo($update11),
                $this->equalTo($update111)
            ));

        $this->manager->showMenuItem($menuName, 'item-1-1', $scope);
    }

    public function testHideMenuItem()
    {
        $menuName = 'menu';
        $scope = new Scope();
        $this->manager->setScopeType($this::SCOPE_TYPE);

        $this->scopeManager->expects($this::exactly(2))
            ->method('find')
            ->with($this::SCOPE_TYPE, null)
            ->willReturn($scope);

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $menu = $this->getMenu();

        $this->builderChainProvider
            ->expects($this->any())
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $update11 = new MenuUpdateStub();
        $update11
            ->setMenu($menuName)
            ->setScope($scope)
            ->setKey('item-1-1')
            ->setParentKey('item-1')
            ->setCustom(false)
            ->setActive(false)
            ->setDefaultTitle('item-1-1')
        ;

        $update111 = new MenuUpdateStub();
        $update111
            ->setMenu($menuName)
            ->setScope($scope)
            ->setKey('item-1-1-1')
            ->setParentKey('item-1-1')
            ->setCustom(false)
            ->setActive(false)
            ->setDefaultTitle('item-1-1-1')
        ;

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->logicalOr(
                $this->equalTo($update11),
                $this->equalTo($update111)
            ));

        $this->manager->hideMenuItem($menuName, 'item-1-1', $scope);
    }

    public function testGetMenu()
    {
        $menu = $this->getMenu();

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with(self::MENU_ID)
            ->will($this->returnValue($menu));

        $this->assertEquals($menu, $this->manager->getMenu('menu'));
    }

    public function testFindMenuItem()
    {
        $scope = new Scope();
        $this->manager->setScopeType($this::SCOPE_TYPE);

        $menu = $this->getMenu();

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with('menu', ['ignoreCache' => true, 'scopeId' => $scope])
            ->will($this->returnValue($menu));

        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $this->assertEquals($item, $this->manager->findMenuItem('menu', 'item-1-1-1', $scope));
    }

    public function testResetMenuUpdatesWithOwnershipType()
    {
        $scope = new Scope();
        $this->manager->setScopeType($this::SCOPE_TYPE);

        $update = new MenuUpdateStub();

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['scopeId' => $scope])
            ->will($this->returnValue([$update]));

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($update);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->with([$update]);

        $this->manager->resetMenuUpdatesWithOwnershipType($scope);
    }
}
