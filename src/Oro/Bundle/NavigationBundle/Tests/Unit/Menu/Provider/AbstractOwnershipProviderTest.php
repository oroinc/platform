<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;

class AbstractOwnershipProviderTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = '\EntityClass';

    /**
     * @var OwnershipProviderStub
     */
    private $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository
     */
    private $entityRepository;

    public function setUp()
    {
        $this->entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->getMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->entityRepository);

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($manager);

        $this->provider = new OwnershipProviderStub($registry, self::ENTITY_CLASS);
    }

    public function testGetMenuUpdates()
    {
        $menuName = 'test_menu';

        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'menu' => $menuName,
                    'ownershipType' => 'stub_type',
                    'ownerId' => 34
                ]
            );

        $this->provider->getMenuUpdates($menuName);
    }
}
