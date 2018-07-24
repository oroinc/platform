<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Storage;

use Oro\Bundle\ImapBundle\Mail\Storage\Imap;
use Zend\Mail\Storage\Exception\InvalidArgumentException;

class ImapTest extends \PHPUnit\Framework\TestCase
{
    public function testCacheForGetNumberByUniqueId()
    {
        $ids = [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5
        ];

        $protocolImap = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Protocol\Imap')
            ->disableOriginalConstructor()->getMock();
        $protocolImap->expects(self::once())->method('select')->willReturn(['uidvalidity'=>'']);
        $protocolImap->expects(self::once())->method('fetch')->willReturn($ids);

        $imap = new Imap($protocolImap);

        $id = '3';
        self::assertEquals(3, $imap->getNumberByUniqueId($id));

        $protocolImap->expects(self::never())->method('fetch');
        self::assertEquals(3, $imap->getNumberByUniqueId($id));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage unique id not found
     */
    public function testExceptionForGetNumberByUniqueId()
    {
        $ids = [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5
        ];

        $protocolImap = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Protocol\Imap')
            ->disableOriginalConstructor()->getMock();
        $protocolImap->expects(self::once())->method('select')->willReturn(['uidvalidity'=>'']);
        $protocolImap->expects(self::once())->method('fetch')->willReturn($ids);

        $imap = new Imap($protocolImap);

        $id = '6';
        $imap->getNumberByUniqueId($id);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage unique id not found
     */
    public function testCacheForGetNumberByUniqueIdNotArray()
    {
        $ids = 3;

        $protocolImap = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Protocol\Imap')
            ->disableOriginalConstructor()->getMock();
        $protocolImap->expects(self::once())->method('select')->willReturn(['uidvalidity'=>'']);
        $protocolImap->expects(self::once())->method('fetch')->willReturn($ids);

        $imap = new Imap($protocolImap);

        $id = '3';
        $imap->getNumberByUniqueId($id);
    }
}
