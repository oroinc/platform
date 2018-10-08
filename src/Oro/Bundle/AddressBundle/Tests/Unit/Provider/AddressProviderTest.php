<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Provider\AddressProvider;

class AddressProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AddressProvider
     */
    private $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $storageMock;

    /**
     * Environment setup
     */
    protected function setUp()
    {
        $this->provider = new AddressProvider();
        $this->storageMock = $this->createMock('Oro\Bundle\AddressBundle\Entity\Manager\StorageInterface');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEmptyAliasException()
    {
        $this->provider->addStorage($this->storageMock, '');
    }

    public function testGetStorageResultNull()
    {
        $this->provider->addStorage($this->storageMock, 'test');

        $this->assertNull($this->provider->getStorage('not_exists_one'));
    }

    public function testGetStorageResult()
    {
        $this->provider->addStorage($this->storageMock, 'test');

        $this->assertEquals($this->storageMock, $this->provider->getStorage('test'));
    }
}
