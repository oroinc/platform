<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Knp\Menu\ItemInterface;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;

class MenuUpdateManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

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

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
        $entityManager = $this->getMock(EntityManager::class, [], [], '', false);
        $entityManager
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
            ->will($this->returnValue($entityManager));

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

    public function testGetMenuUpdateByKeyDatabase()
    {
        $menuName = 'test-menu';
        $key = 'test-key';
        $ownershipType = MenuUpdate::OWNERSHIP_USER;
        $ownerId = 1;

        $update = new MenuUpdateStub();

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['menu' => $menuName, 'key' => $key, 'ownershipType' => $ownershipType, 'ownerId' => $ownerId])
            ->will($this->returnValue($update));

        $result = $this->manager->getMenuUpdateByKeyAndScope($menuName, $key, $ownershipType, $ownerId);

        $this->assertEquals($update, $result);
    }

    public function testGetMenuUpdateByKeyYml()
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
        $menu->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('test-item'));

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
            ->with($update, $item, 'test-item');

        $result = $this->manager->getMenuUpdateByKeyAndScope($menuName, $key, $ownershipType, $ownerId);

        $this->assertEquals($update, $result);
    }

    public function testGetMenuUpdateByKeyEmpty()
    {
        $menuName = 'test-menu';
        $key = 'test-key';
        $ownershipType = MenuUpdate::OWNERSHIP_USER;
        $ownerId = 1;

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

        $this->assertNull($result);
    }
}
