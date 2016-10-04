<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Provider;

use Doctrine\ORM\EntityRepository;

class AbstractOwnershipProviderTest extends \PHPUnit_Framework_TestCase
{
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
            ->setMethods(['getMenuUpdates'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new OwnershipProviderStub($this->entityRepository);
    }

    public function testGetMenuUpdates()
    {
        $menuName = 'test_menu';

        $this->entityRepository->expects($this->once())
            ->method('getMenuUpdates')
            ->with($menuName, $this->provider->getType(), $this->provider->getId());

        $this->provider->getMenuUpdates($menuName);
    }
}
