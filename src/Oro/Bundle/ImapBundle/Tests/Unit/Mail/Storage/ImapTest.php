<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Storage;

use Laminas\Mail\Storage\Exception\InvalidArgumentException;
use Oro\Bundle\ImapBundle\Mail\Protocol\Exception\InvalidEmailFormatException;
use Oro\Bundle\ImapBundle\Mail\Protocol\Imap as ImapProtocol;
use Oro\Bundle\ImapBundle\Mail\Storage\Imap;
use PHPUnit\Framework\TestCase;

class ImapTest extends TestCase
{
    public function testCacheForGetNumberByUniqueId(): void
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
            ->willReturn(['uidvalidity' => '']);
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

    public function testExceptionForGetNumberByUniqueId(): void
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
            ->willReturn(['uidvalidity' => '']);
        $protocolImap->expects(self::once())
            ->method('fetch')
            ->willReturn($ids);

        $imap = new Imap($protocolImap);

        $id = '6';
        $imap->getNumberByUniqueId($id);
    }

    public function testCacheForGetNumberByUniqueIdNotArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unique id not found');

        $ids = 3;

        $protocolImap = $this->createMock(ImapProtocol::class);
        $protocolImap->expects(self::once())
            ->method('select')
            ->willReturn(['uidvalidity' => '']);
        $protocolImap->expects(self::once())
            ->method('fetch')
            ->willReturn($ids);

        $imap = new Imap($protocolImap);

        $id = '3';
        $imap->getNumberByUniqueId($id);
    }

    /**
     * @dataProvider invalidEmailFormatExceptionDataProvider
     */
    public function testInvalidEmailFormatException(array $readLineTokens): void
    {
        $protocolImap = $this->getMockBuilder(ImapProtocol::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendRequest', 'readLine'])
            ->getMock();
        $protocolImap->expects(self::once())
            ->method('readLine')
            ->willReturnCallback(function (&$tokens) use ($readLineTokens) {
                $tokens = $readLineTokens;
                return true;
            });

        $this->expectException(InvalidEmailFormatException::class);
        $protocolImap->fetch('test_item', 'from@example.com');
    }

    public function invalidEmailFormatExceptionDataProvider(): array
    {
        return [
            'OK FETCH completed' => [
                'readLineTokens' => ['OK', 'FETCH', 'completed.']
            ],
            'FAIL FETCH error' => [
                'readLineTokens' => ['FAIL', 'FETCH', 'error.']
            ]
        ];
    }
}
