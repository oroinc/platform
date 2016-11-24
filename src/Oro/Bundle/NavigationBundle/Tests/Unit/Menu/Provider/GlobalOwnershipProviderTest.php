<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\NavigationBundle\Menu\Provider\GlobalOwnershipProvider;

class GlobalOwnershipProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GlobalOwnershipProvider
     */
    private $provider;

    public function setUp()
    {
        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new GlobalOwnershipProvider($registry, '\EntityClass');
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
