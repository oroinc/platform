<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector;

use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Connector\ImapServices;
use Oro\Bundle\ImapBundle\Connector\ImapServicesFactory;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryBuilder;
use Oro\Bundle\ImapBundle\Connector\Search\SearchStringManagerInterface;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;
use Oro\Bundle\ImapBundle\Mail\Storage\Imap;

class ImapConnectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Imap|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var SearchStringManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $searchStringManager;

    /** @var ImapConnector */
    private $connector;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(Imap::class);
        $this->storage->expects($this->any())
            ->method('__destruct');

        $this->searchStringManager = $this->createMock(SearchStringManagerInterface::class);
        $this->searchStringManager->expects($this->any())
            ->method('isAcceptableItem')
            ->willReturn(true);
        $this->searchStringManager->expects($this->any())
            ->method('buildSearchString')
            ->willReturn('some query');

        $services = new ImapServices($this->storage, $this->searchStringManager);

        $factory = $this->createMock(ImapServicesFactory::class);
        $factory->expects($this->once())
            ->method('createImapServices')
            ->willReturn($services);

        $this->connector = new ImapConnector(new ImapConfig(), $factory);
    }

    public function testGetSearchQueryBuilder()
    {
        $builder = $this->connector->getSearchQueryBuilder();
        $this->assertInstanceOf(SearchQueryBuilder::class, $builder);
    }

    public function testFindItemsWithNoArguments()
    {
        $this->storage->expects($this->never())
            ->method('selectFolder');
        $this->storage->expects($this->never())
            ->method('search');
        $this->storage->expects($this->never())
            ->method('getMessage');

        $result = $this->connector->findItems();
        $this->assertCount(0, $result);
    }

    public function testFindItemsWithSearchQuery()
    {
        $this->storage->expects($this->once())
            ->method('search')
            ->with(['some query'])
            ->willReturn(['1', '2']);
        $this->storage->expects($this->never())
            ->method('getMessage')
            ->willReturn(new \stdClass());

        $result = $this->connector->findItems($this->connector->getSearchQueryBuilder()->get());
        $this->assertCount(2, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testFindItemsWithSearchQueryGetMessages()
    {
        $this->storage->expects($this->once())
            ->method('search')
            ->with(['some query'])
            ->willReturn(['1', '2']);
        $this->storage->expects($this->exactly(2))
            ->method('getMessage')
            ->willReturn(new \stdClass());

        $result = $this->connector->findItems($this->connector->getSearchQueryBuilder()->get());
        $this->assertCount(2, $result);
        foreach ($result as $r) {
            // idle iteration is necessary here
        }
    }

    public function testFindUIDs()
    {
        $this->storage->expects($this->once())
            ->method('uidSearch')
            ->with(['some query'])
            ->willReturn(['1', '2']);
        $result = $this->connector->findUIDs('some query');
        $this->assertEquals(['1', '2'], $result);
    }

    public function testFindFolders()
    {
        $folder = $this->createMock(Folder::class);

        $this->storage->expects($this->once())
            ->method('getFolders')
            ->with('SomeFolder')
            ->willReturn($folder);

        $result = $this->connector->findFolders('SomeFolder');
        $this->assertCount(0, $result);
    }

    public function testFindFolder()
    {
        $folder = $this->createMock(Folder::class);

        $this->storage->expects($this->once())
            ->method('getFolders')
            ->with('SomeFolder')
            ->willReturn($folder);

        $result = $this->connector->findFolder('SomeFolder');
        $this->assertSame($folder, $result);
    }

    public function testGetItem()
    {
        $msg = new \stdClass();

        $this->storage->expects($this->once())
            ->method('getNumberByUniqueId')
            ->with(123)
            ->willReturn(12345);
        $this->storage->expects($this->once())
            ->method('getMessage')
            ->with(12345)
            ->willReturn($msg);

        $result = $this->connector->getItem(123);
        $this->assertSame($msg, $result);
    }

    public function testSetFlags()
    {
        $uid = 123;
        $id = 12345;
        $flags = [];

        $this->storage->expects($this->once())
            ->method('getNumberByUniqueId')
            ->with($uid)
            ->willReturn($id);

        $this->storage->expects($this->once())
            ->method('setFlags')
            ->with($id, $flags);

        $response = $this->connector->setFlags($uid, $flags);
        $this->assertInstanceOf(ImapConnector::class, $response);
    }
}
