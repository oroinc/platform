<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use DateTime;
use Laminas\Mail\Header\HeaderInterface;
use Laminas\Mail\Header\MultipleHeadersInterface;
use Laminas\Mail\Headers;
use Oro\Bundle\ImapBundle\Connector\ImapMessageIterator;
use Oro\Bundle\ImapBundle\Mail\Storage\Message;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ImapEmailManagerTest extends TestCase
{
    /** @var ImapEmailManager */
    private $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $connector;

    protected function setUp(): void
    {
        $this->connector = $this->getMockBuilder('Oro\Bundle\ImapBundle\Connector\ImapConnector')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = new ImapEmailManager($this->connector);
    }

    public function testSelectFolder()
    {
        $this->connector->expects($this->once())
            ->method('selectFolder')
            ->with('test');
        $this->connector->expects($this->once())
            ->method('getSelectedFolder')
            ->will($this->returnValue('test'));

        $this->manager->selectFolder('test');
        $this->assertEquals('test', $this->manager->getSelectedFolder());
    }

    /**
     * @dataProvider getEmailsDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws \Exception
     */
    public function testGetEmails($strDate): void
    {
        $toAddress = $this->createMock('Laminas\Mail\Address\AddressInterface');
        $toAddress->expects($this->once())->method('toString')->will($this->returnValue('toEmail'));
        $toAddressList = $this->getMockForAbstractClass(
            'Laminas\Mail\Header\AbstractAddressList',
            [],
            '',
            false,
            false,
            true,
            ['getAddressList', 'getFieldName']
        );
        $toAddressList->expects($this->once())->method('getFieldName')->willReturn('To');
        $toAddressList->expects($this->once())->method('getAddressList')->will($this->returnValue([$toAddress]));

        $ccAddress = $this->createMock('Laminas\Mail\Address\AddressInterface');
        $ccAddress->expects($this->once())->method('toString')->will($this->returnValue('ccEmail'));
        $ccAddressList = $this->getMockForAbstractClass(
            'Laminas\Mail\Header\AbstractAddressList',
            [],
            '',
            false,
            false,
            true,
            ['getAddressList', 'getFieldName']
        );
        $ccAddressList->expects($this->once())->method('getFieldName')->willReturn('Cc');
        $ccAddressList->expects($this->once())->method('getAddressList')->will($this->returnValue([$ccAddress]));

        $bccAddress = $this->createMock('Laminas\Mail\Address\AddressInterface');
        $bccAddress->expects($this->once())->method('toString')->will($this->returnValue('bccEmail'));
        $bccAddressList = $this->getMockForAbstractClass(
            'Laminas\Mail\Header\AbstractAddressList',
            [],
            '',
            false,
            false,
            true,
            ['getAddressList', 'getFieldName']
        );
        $bccAddressList->expects($this->once())->method('getFieldName')->willReturn('Bcc');
        $bccAddressList->expects($this->once())->method('getAddressList')->will($this->returnValue([$bccAddress]));

        $this->connector->expects($this->once())
            ->method('getUidValidity')
            ->will($this->returnValue(456));
        $msg = $this->getMessageMock(
            [
                $this->getHeader('UID', '123'),
                $this->getHeader('Subject', 'Subject'),
                $this->getHeader('From', 'fromEmail'),
                $this->getHeader('Date', $strDate),
                $this->getHeader('Received', 'by server to email; ' . str_replace('59:', '58:', $strDate)),
                $this->getHeader('InternalDate', str_replace('59:', '57:', $strDate)),
                $this->getHeader('Message-ID', 'MessageId'),
                $this->getHeader('X-GM-MSG-ID', 'XMsgId'),
                $this->getHeader('X-GM-THR-ID', 'XThrId'),
                $toAddressList,
                $ccAddressList,
                $bccAddressList,
                $this->getHeader('References', 'References'),
                $this->getHeader('Accept-Language', 'Accept-Language'),
            ]
        );

        $msg->expects($this->exactly(2))
            ->method('getFlags')
            ->will($this->returnValue(['test1', 'test2']));

        $query = $this->getMockBuilder('Oro\Bundle\ImapBundle\Connector\Search\SearchQuery')
            ->disableOriginalConstructor()
            ->getMock();

        $imap = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Storage\Imap')
            ->disableOriginalConstructor()
            ->getMock();
        $imap->expects($this->any())->method('getMessage')->will($this->returnValue($msg));
        $messageIterator = new ImapMessageIterator($imap, [1]);
        $this->connector->expects($this->once())
            ->method('findItems')
            ->with($this->identicalTo($query))
            ->will($this->returnValue($messageIterator));

        $this->manager->selectFolder('Test Folder');
        $emails = $this->manager->getEmails($query);

        $this->assertCount(1, $emails);

        $emails->rewind();
        $this->assertTrue($emails->valid());
        $email = $emails->current();
        $this->assertEquals(123, $email->getId()->getUid());
        $this->assertEquals(456, $email->getId()->getUidValidity());
        $this->assertEquals('Subject', $email->getSubject());
        $this->assertEquals('fromEmail', $email->getFrom());
        $this->assertEquals(
            new DateTime('2011-06-30 23:59:59', new \DateTimeZone('UTC')),
            $email->getSentAt()
        );
        $this->assertEquals(
            new DateTime('2011-06-30 23:58:59', new \DateTimeZone('UTC')),
            $email->getReceivedAt()
        );
        $this->assertEquals(
            new DateTime('2011-06-30 23:57:59', new \DateTimeZone('UTC')),
            $email->getInternalDate()
        );
        $this->assertEquals(0, $email->getImportance());
        $this->assertEquals('MessageId', $email->getMessageId());
        $this->assertEquals('References', $email->getRefs());
        $this->assertEquals(false, $email->hasFlag('test'));
        $this->assertEquals(true, $email->hasFlag('test1'));
        $this->assertEquals('XMsgId', $email->getXMessageId());
        $this->assertEquals('XThrId', $email->getXThreadId());
        $toRecipients = $email->getToRecipients();
        $this->assertEquals('toEmail', $toRecipients[0]);
        $ccRecipients = $email->getCcRecipients();
        $this->assertEquals('ccEmail', $ccRecipients[0]);
        $bccRecipients = $email->getBccRecipients();
        $this->assertEquals('bccEmail', $bccRecipients[0]);
    }

    public function testGetUnseenEmailUIDs(): void
    {
        $startDate = new DateTime('29-05-2015');

        $this->connector->expects($this->at(0))
            ->method('findUIDs')
            ->with('UNSEEN SINCE 29-May-2015');

        $this->manager->getUnseenEmailUIDs($startDate);
    }

    public function testConvertToEmailWithUnexpectedMultiValueHeader(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot parse email message. Subject: Subject. Error:'
            . ' It is expected that the header "X-GM-THR-ID" has a string value, but several values are returned.'
            . ' Values: "XThrId1", "XThrId2".'
        );

        $msg = $this->getMessageMock(
            [
                $this->getHeader('UID', '123'),
                $this->getHeader('Subject', 'Subject'),
                $this->getHeader('From', 'fromEmail'),
                $this->getHeader('Date', 'Fri, 31 Jun 2011 10:59:59 +1100'),
                $this->getHeader('Received', 'by server to email; Fri, 31 Jun 2011 10:58:58 +1100'),
                $this->getHeader('InternalDate', 'Fri, 31 Jun 2011 10:57:57 +1100'),
                $this->getHeader('References', 'References'),
                $this->getHeader('X-GM-MSG-ID', 'XMsgId'),
                $this->getHeader('X-GM-THR-ID', 'XThrId1'),
                $this->getHeader('X-GM-THR-ID', 'XThrId2'),
            ]
        );

        $this->manager->convertToEmail($msg);
    }

    public function testConvertToEmailWithMultiValueMessageId(): void
    {
        $msg = $this->getMessageMock(
            [
                $this->getHeader('UID', '123'),
                $this->getHeader('Subject', str_pad('Subject', 1000, '!')),
                $this->getHeader('Date', 'Fri, 31 Jun 2011 10:59:59 +1100'),
                $this->getHeader('Received', 'by server to email; Fri, 31 Jun 2011 10:58:58 +1100'),
                $this->getMultiValueHeaderMessageId('Message-ID', 'Message-ID'),
                $this->getMultiValueHeaderMessageId('Message-ID', 'MessageId'),
            ]
        );

        $email = $this->manager->convertToEmail($msg);

        $this->assertNotEmpty($email->getMessageId());
        $this->assertIsArray($email->getMultiMessageId());
        $this->assertCount(2, $email->getMultiMessageId());
    }

    public function testConvertToEmailWithMultiValueAcceptLanguage(): void
    {
        $msg = $this->getMessageMock(
            [
                $this->getHeader('UID', '123'),
                $this->getMultiValueHeader('Accept-Language', 'en-US'),
                $this->getMultiValueHeader('Accept-Language', 'en-US'),
                $this->getHeader('Date', 'Fri, 31 Jun 2011 10:59:59 +1100'),
                $this->getHeader('Received', 'by server to email; Fri, 31 Jun 2011 10:58:58 +1100'),
                $this->getHeader('Message-ID', 'MessageId')
            ]
        );

        $email = $this->manager->convertToEmail($msg);

        $this->assertNotEmpty($email->getMessageId());
        $this->assertEquals('en-US', $email->getAcceptLanguageHeader());
    }

    public function testConvertToEmailWithLongSubject(): void
    {
        $msg = $this->getMessageMock(
            [
                $this->getHeader('UID', '123'),
                $this->getHeader('Subject', str_pad('Subject', 1000, '!')),
                $this->getHeader('Date', 'Fri, 31 Jun 2011 10:59:59 +1100'),
                $this->getHeader('Received', 'by server to email; Fri, 31 Jun 2011 10:58:58 +1100'),
                $this->getHeader('Message-ID', 'MessageId')
            ]
        );

        $email = $this->manager->convertToEmail($msg);
        $this->assertEquals(998, mb_strlen($email->getSubject()));
    }

    public function testConvertToEmailWithSeveralSubjects(): void
    {
        $msg = $this->getMessageMock(
            [
                $this->getHeader('UID', '123'),
                $this->getMultiValueHeader('Subject', 'Subject1'),
                $this->getMultiValueHeader('Subject', 'Subject2'),
                $this->getHeader('Date', 'Fri, 31 Jun 2011 10:59:59 +1100'),
                $this->getHeader('Received', 'by server to email; Fri, 31 Jun 2011 10:58:58 +1100'),
                $this->getHeader('Message-ID', 'MessageId')
            ]
        );

        $email = $this->manager->convertToEmail($msg);
        $this->assertEquals('Subject1', $email->getSubject());
    }

    public function getEmailsDataProvider(): array
    {
        return [
            ['Fri, 31 Jun 2011 10:59:59 +1100'],
            ['Thu, 30 Jun 2011 23:59:59 0000'],
            ['Fri, 31 Jun 2011 10:59:59 +11:00 (GMT+11:00)'],
            ['Fri, 31 06 2011 10:59:59 +1100']
        ];
    }

    public function testConvertToEmailWithReceivedHeader(): void
    {
        $msg = $this->getMessageMock(
            [
                $this->getHeader('UID', '123'),
                $this->getHeader('Date', 'Fri, 31 Jun 2011 10:59:59 +1100'),
                $this->getHeader('Received', 'by server to email; Fri, 31 Jun 2011 10:58:58 +1100'),
                $this->getHeader('Message-ID', 'MessageId'),
            ]
        );

        $email = $this->manager->convertToEmail($msg);
        $this->assertEquals(
            new DateTime('Fri, 31 Jun 2011 10:58:58 +1100', new \DateTimeZone('UTC')),
            $email->getReceivedAt()
        );
    }

    public function testConvertToEmailWithoutReceivedHeader(): void
    {
        $msg = $this->getMessageMock(
            [
                $this->getHeader('UID', '123'),
                $this->getHeader('Date', 'Fri, 31 Jun 2011 10:59:59 +1100'),
                $this->getHeader('Message-ID', 'MessageId')
            ]
        );

        $email = $this->manager->convertToEmail($msg);
        $this->assertEquals(
            new DateTime('Fri, 31 Jun 2011 10:59:59 +1100', new \DateTimeZone('UTC')),
            $email->getReceivedAt()
        );
    }

    public function testConvertToEmailWithoutReceivedAndMessageIDHeadersAndWithInternalDateHeader(): void
    {
        $msg = $this->getMessageMock(
            [
                $this->getHeader('UID', '123'),
                $this->getHeader('Date', 'Fri, 31 Jun 2011 10:59:59 +1100'),
                $this->getHeader('InternalDate', 'Fri, 25 Jun 2011 10:59:59 +1100')
            ]
        );

        $email = $this->manager->convertToEmail($msg);
        $this->assertEquals(
            md5('Fri, 25 Jun 2011 10:59:59 +1100'),
            $email->getMessageId()
        );
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return HeaderInterface|MockObject
     */
    protected function getHeader($name, $value)
    {
        $header = $this->createMock('Laminas\Mail\Header\HeaderInterface');
        $header->expects($this->atLeastOnce())
            ->method('getFieldName')
            ->willReturn($name);
        $header->expects($this->atLeastOnce())
            ->method('getFieldValue')
            ->willReturn($value);

        return $header;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return MultipleHeadersInterface|MockObject
     */
    protected function getMultiValueHeader($name, $value)
    {
        $header = $this->createMock('Laminas\Mail\Header\MultipleHeadersInterface');
        $header->expects($this->any())
            ->method('getFieldName')
            ->willReturn($name);
        $header->expects($this->any())
            ->method('getFieldValue')
            ->willReturn($value);

        return $header;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return MultipleHeadersInterface|MockObject
     */
    protected function getMultiValueHeaderMessageId($name, $value)
    {
        $header = $this->createMock('Laminas\Mail\Header\MultipleHeadersInterface');
        $header->expects($this->atLeastOnce())
            ->method('getFieldName')
            ->willReturn($name);
        $header->expects($this->atLeastOnce())
            ->method('getFieldValue')
            ->willReturn($value);

        return $header;
    }

    /**
     * Returns mock of Message object with injected headers
     *
     * @param array $headers headers array which will be injected into message mock
     * @return Message|MockObject
     */
    private function getMessageMock(array $headers)
    {
        $msg = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Storage\Message')
            ->disableOriginalConstructor()
            ->getMock();
        $messageHeaders = new Headers();
        $messageHeaders->addHeaders($headers);

        $msg->expects($this->once())
            ->method('getHeaders')
            ->willReturn($messageHeaders);

        return $msg;
    }
}
