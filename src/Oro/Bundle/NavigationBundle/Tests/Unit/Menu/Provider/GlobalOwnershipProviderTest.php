<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Provider;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\NavigationBundle\Menu\Provider\GlobalOwnershipProvider;

class GlobalOwnershipProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GlobalOwnershipProvider
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

        $this->provider = new GlobalOwnershipProvider($this->entityRepository);
    }

    public function testGetId()
    {
        $this->assertEquals(0, $this->provider->getId());
    }

    public function testGetType()
    {
        $this->assertEquals('global', $this->provider->getType());

    }
}
