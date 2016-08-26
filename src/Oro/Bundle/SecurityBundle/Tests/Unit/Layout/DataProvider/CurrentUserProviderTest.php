<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\SecurityBundle\Layout\DataProvider\CurrentUserProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CurrentUserProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var CurrentUserProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityFacade = $this->getMock(SecurityFacade::class, [], [], '', false);
        $this->provider = new CurrentUserProvider($this->securityFacade);
    }

    public function testGetCurrentUserLoggedIn()
    {
        $object = new \stdClass();

        $this->securityFacade
            ->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($object));

        $this->assertEquals($object, $this->provider->getCurrentUser());
    }

    public function testGetCurrentUserLoggedOut()
    {
        $this->securityFacade
            ->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue(null));

        $this->assertNull($this->provider->getCurrentUser());
    }
}
