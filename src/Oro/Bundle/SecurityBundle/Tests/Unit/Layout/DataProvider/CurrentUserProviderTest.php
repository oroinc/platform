<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Layout\DataProvider\CurrentUserProvider;

class CurrentUserProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var CurrentUserProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->provider = new CurrentUserProvider($this->tokenAccessor);
    }

    public function testGetCurrentUserLoggedIn()
    {
        $object = new \stdClass();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($object));

        $this->assertEquals($object, $this->provider->getCurrentUser());
    }

    public function testGetCurrentUserLoggedOut()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue(null));

        $this->assertNull($this->provider->getCurrentUser());
    }
}
