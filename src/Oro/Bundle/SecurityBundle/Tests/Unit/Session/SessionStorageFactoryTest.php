<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Session;

use Oro\Bundle\SecurityBundle\Session\SessionStorageFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageFactoryInterface;

class SessionStorageFactoryTest extends \PHPUnit\Framework\TestCase
{
    private SessionStorageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $innerSessionStorageFactory;

    private SessionBagInterface $sessionBag;

    private SessionStorageFactory $factory;

    protected function setUp(): void
    {
        $this->innerSessionStorageFactory = $this->createMock(SessionStorageFactoryInterface::class);
        $this->sessionBag = new AttributeBag('sample_bag');
        $this->sessionBag->setName('sample_bag_name');
        $this->factory = new SessionStorageFactory($this->innerSessionStorageFactory, [$this->sessionBag]);
    }

    public function testCreateStorage(): void
    {
        $request = new Request();
        $this->innerSessionStorageFactory->expects(self::once())
            ->method('createStorage')
            ->with($request)
            ->willReturn(new MockArraySessionStorage());

        $storage = $this->factory->createStorage($request);

        self::assertSame($this->sessionBag, $storage->getBag('sample_bag_name'));
    }
}
