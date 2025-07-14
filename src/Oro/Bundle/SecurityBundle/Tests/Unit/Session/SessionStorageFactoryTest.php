<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Session;

use Oro\Bundle\SecurityBundle\Session\SessionStorageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageFactoryInterface;

class SessionStorageFactoryTest extends TestCase
{
    private SessionStorageFactoryInterface&MockObject $innerSessionStorageFactory;
    private SessionBagInterface $sessionBag;
    private SessionStorageFactory $factory;

    #[\Override]
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
