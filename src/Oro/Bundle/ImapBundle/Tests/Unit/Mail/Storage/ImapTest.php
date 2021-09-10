<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Storage;

use Laminas\Mail\Storage\Exception\InvalidArgumentException;
use Oro\Bundle\ImapBundle\Mail\Protocol\Imap as ImapProtocol;
use Oro\Bundle\ImapBundle\Mail\Storage\Imap;

class ImapTest extends \PHPUnit\Framework\TestCase
{
    public function testCacheForGetNumberByUniqueId()
    {
        $ids = [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
        ];

        $protocolImap = $this->createMock(ImapProtocol::class);
        $protocolImap->expects(self::once())
            ->method('select')
            ->willReturn(['uidvalidity'=>'']);
        $protocolImap->expects(self::once())
            ->method('fetch')
            ->willReturn($ids);

        $imap = new Imap($protocolImap);

        $id = '3';
        self::assertEquals(3, $imap->getNumberByUniqueId($id));

        $protocolImap->expects(self::never())
            ->method('fetch');
        self::assertEquals(3, $imap->getNumberByUniqueId($id));
    }

    public function testExceptionForGetNumberByUniqueId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unique id not found');

        $ids = [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5
        ];

        $protocolImap = $this->createMock(ImapProtocol::class);
        $protocolImap->expects(self::once())
            ->method('select')
            ->willReturn(['uidvalidity'=>'']);
        $protocolImap->expects(self::once())
            ->method('fetch')
            ->willReturn($ids);

        $imap = new Imap($protocolImap);

        $id = '6';
        $imap->getNumberByUniqueId($id);
    }

    public function testCacheForGetNumberByUniqueIdNotArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unique id not found');

        $ids = 3;

        $protocolImap = $this->createMock(ImapProtocol::class);
        $protocolImap->expects(self::once())
            ->method('select')
            ->willReturn(['uidvalidity'=>'']);
        $protocolImap->expects(self::once())
            ->method('fetch')
            ->willReturn($ids);

        $imap = new Imap($protocolImap);

        $id = '3';
        $imap->getNumberByUniqueId($id);
    }
}
