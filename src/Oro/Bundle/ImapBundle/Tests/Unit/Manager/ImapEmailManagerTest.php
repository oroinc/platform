<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager\DTO;

use Zend\Mail\Header\HeaderInterface;

use Oro\Bundle\ImapBundle\Mail\Storage\Message;
use Oro\Bundle\ImapBundle\Connector\ImapMessageIterator;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;

class ImapEmailManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImapEmailManager */
    private $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $connector;

    protected function setUp()
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
     * @dataProvider getEmailsProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetEmails($strDate)
    {
        $toAddress = $this->getMock('Zend\Mail\Address\AddressInterface');
        $toAddress->expects($this->once())->method('toString')->will($this->returnValue('toEmail'));
        $toAddressList = $this->getMockForAbstractClass(
            'Zend\Mail\Header\AbstractAddressList',
            [],
            '',
            false,
            false,
            true,
            ['getAddressList']
        );
        $toAddressList->expects($this->once())->method('getAddressList')->will($this->returnValue([$toAddress]));

        $ccAddress = $this->getMock('Zend\Mail\Address\AddressInterface');
        $ccAddress->expects($this->once())->method('toString')->will($this->returnValue('ccEmail'));
        $ccAddressList = $this->getMockForAbstractClass(
            'Zend\Mail\Header\AbstractAddressList',
            [],
            '',
            false,
            false,
            true,
            ['getAddressList']
        );
        $ccAddressList->expects($this->once())->method('getAddressList')->will($this->returnValue([$ccAddress]));

        $bccAddress = $this->getMock('Zend\Mail\Address\AddressInterface');
        $bccAddress->expects($this->once())->method('toString')->will($this->returnValue('bccEmail'));
        $bccAddressList = $this->getMockForAbstractClass(
            'Zend\Mail\Header\AbstractAddressList',
            [],
            '',
            false,
            false,
            true,
            ['getAddressList']
        );
        $bccAddressList->expects($this->once())->method('getAddressList')->will($this->returnValue([$bccAddress]));

        $this->connector->expects($this->once())
            ->method('getUidValidity')
            ->will($this->returnValue(456));
        $msg = $this->getMessageMock(
            [
                ['UID', $this->getHeader('123')],
                ['Subject', $this->getHeader('Subject')],
                ['From', $this->getHeader('fromEmail')],
                ['Date', $this->getHeader($strDate)],
                ['Received', $this->getHeader('by server to email; ' . str_replace('59:', '58:', $strDate))],
                ['InternalDate', $this->getHeader(str_replace('59:', '57:', $strDate))],
                ['Importance', false],
                ['Message-ID', $this->getHeader('MessageId')],
                ['X-GM-MSG-ID', $this->getHeader('XMsgId')],
                ['X-GM-THR-ID', $this->getHeader('XThrId')],
                ['X-GM-LABELS', false],
                ['To', $toAddressList],
                ['Cc', $ccAddressList],
                ['Bcc', $bccAddressList],
                ['References', $this->getHeader('References')],
                ['Accept-Language', $this->getHeader('Accept-Language')],
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
            new \DateTime('2011-06-30 23:59:59', new \DateTimeZone('UTC')),
            $email->getSentAt()
        );
        $this->assertEquals(
            new \DateTime('2011-06-30 23:58:59', new \DateTimeZone('UTC')),
            $email->getReceivedAt()
        );
        $this->assertEquals(
            new \DateTime('2011-06-30 23:57:59', new \DateTimeZone('UTC')),
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

    public function testGetUnseenEmailUIDs()
    {
        $startDate = new \DateTime('29-05-2015');

        $this->connector->expects($this->at(0))
            ->method('findUIDs')
            ->with('UNSEEN SINCE 29-May-2015');

        $this->manager->getUnseenEmailUIDs($startDate);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot parse email message. Subject: Subject. Error: It is expected that the header "X-GM-THR-ID" has a string value, but several values are returned. Values: "XThrId1", "XThrId2".
     */
    // @codingStandardsIgnoreEnd
    public function testConvertToEmailWithUnexpectedMultiValueHeader()
    {
        $msg = $this->getMessageMock(
            [
                ['UID', $this->getHeader('123')],
                ['Subject', $this->getHeader('Subject')],
                ['From', $this->getHeader('fromEmail')],
                ['Date', $this->getHeader('Fri, 31 Jun 2011 10:59:59 +1100')],
                ['Received', $this->getHeader('by server to email; Fri, 31 Jun 2011 10:58:58 +1100')],
                ['InternalDate', $this->getHeader('Fri, 31 Jun 2011 10:57:57 +1100')],
                ['Importance', false],
                ['References', $this->getHeader('References')],
                ['X-GM-MSG-ID', $this->getHeader('XMsgId')],
                ['X-GM-THR-ID', $this->getMultiValueHeader(['XThrId1', 'XThrId2'])],
                ['X-GM-LABELS', false]
            ]
        );

        $this->manager->convertToEmail($msg);
    }

    public function testConvertToEmailWithMultiValueMessageId()
    {
        $msg = $this->getMessageMock(
            [
                ['UID', $this->getHeader('123')],
                ['Subject', $this->getHeader('Subject')],
                ['From', $this->getHeader('fromEmail')],
                ['Date', $this->getHeader('Fri, 31 Jun 2011 10:59:59 +1100')],
                ['Received', $this->getHeader('by server to email; Fri, 31 Jun 2011 10:58:58 +1100')],
                ['InternalDate', $this->getHeader('Fri, 31 Jun 2011 10:57:57 +1100')],
                ['Importance', false],
                ['Message-ID', $this->getMultiValueHeaderMessageId(['MessageId1', 'MessageId2'])],
                ['References', $this->getHeader('References')],
                ['X-GM-MSG-ID', $this->getHeader('XMsgId')],
                ['X-GM-THR-ID', $this->getHeader('XThrId1')],
                ['X-GM-LABELS', false],
                ['Accept-Language', $this->getHeader('Accept-Language')],
            ]
        );

        $email = $this->manager->convertToEmail($msg);

        $this->assertNotEmpty($email->getMessageId());
        $this->assertInternalType('array', $email->getMultiMessageId());
        $this->assertCount(2, $email->getMultiMessageId());
    }

    public function testConvertToEmailWithMultiValueAcceptLanguage()
    {
        $msg = $this->getMessageMock(
            [
                ['UID', $this->getHeader('123')],
                ['Subject', $this->getHeader('Subject')],
                ['From', $this->getHeader('fromEmail')],
                ['Date', $this->getHeader('Fri, 31 Jun 2011 10:59:59 +1100')],
                ['Received', $this->getHeader('by server to email; Fri, 31 Jun 2011 10:58:58 +1100')],
                ['InternalDate', $this->getHeader('Fri, 31 Jun 2011 10:57:57 +1100')],
                ['Message-ID', $this->getHeader('MessageId')],
                ['Importance', false],
                ['References', $this->getHeader('References')],
                ['X-GM-MSG-ID', $this->getHeader('XMsgId')],
                ['X-GM-THR-ID', $this->getHeader('XThrId1')],
                ['X-GM-LABELS', false],
                ['Accept-Language', $this->getMultiValueHeader(['en-US', 'en-US'])],
            ]
        );

        $email = $this->manager->convertToEmail($msg);

        $this->assertNotEmpty($email->getMessageId());
        $this->assertEquals('en-US', $email->getAcceptLanguageHeader());
    }

    public function testConvertToEmailWithLongSubject()
    {
        $msg = $this->getMessageMock(
            [
                ['UID', $this->getHeader('123')],
                ['Subject', $this->getHeader(str_pad('Subject', 1000, '!'))],
                ['From', $this->getHeader('fromEmail')],
                ['Date', $this->getHeader('Fri, 31 Jun 2011 10:59:59 +1100')],
                ['Received', $this->getHeader('by server to email; Fri, 31 Jun 2011 10:58:58 +1100')],
                ['InternalDate', $this->getHeader('Fri, 31 Jun 2011 10:57:57 +1100')],
                ['Message-ID', $this->getHeader('MessageId')],
                ['Importance', false],
                ['References', $this->getHeader('References')],
                ['X-GM-MSG-ID', $this->getHeader('XMsgId')],
                ['X-GM-THR-ID', $this->getHeader('XThrId1')],
                ['X-GM-LABELS', false],
                ['Accept-Language', $this->getMultiValueHeader(['en-US', 'en-US'])],
            ]
        );

        $email = $this->manager->convertToEmail($msg);
        $this->assertEquals(998, mb_strlen($email->getSubject()));
    }

    public function getEmailsProvider()
    {
        return [
            ['Fri, 31 Jun 2011 10:59:59 +1100'],
            ['Fri, 31 Jun 2011 10:59:59 +11:00 (GMT+11:00)'],
            ['Fri, 31 06 2011 10:59:59 +1100']
        ];
    }

    /**
     * @param mixed $value
     *
     * @return HeaderInterface
     */
    protected function getHeader($value)
    {
        $header = $this->getMock('Zend\Mail\Header\HeaderInterface');
        $header->expects($this->once())
            ->method('getFieldValue')
            ->will($this->returnValue($value));

        return $header;
    }

    /**
     * @param array $values
     *
     * @return \ArrayIterator|HeaderInterface[]
     */
    protected function getMultiValueHeader(array $values)
    {
        $headers = [];
        foreach ($values as $value) {
            $header = $this->getMock('Zend\Mail\Header\HeaderInterface');
            $header->expects($this->once())
                ->method('getFieldValue')
                ->will($this->returnValue($value));
            $headers[] = $header;
        }

        return new \ArrayIterator($headers);
    }

    /**
     * @param array $values
     * @return \ArrayIterator
     */
    protected function getMultiValueHeaderMessageId(array $values)
    {
        $headers = [];
        foreach ($values as $value) {
            $exactly = 1;
            if (count($headers) === 0) {
                $exactly = 2;
            }

            $header = $this->getMock('Zend\Mail\Header\HeaderInterface');
            $header->expects($this->exactly($exactly))
                ->method('getFieldValue')
                ->will($this->returnValue($value));
            $headers[] = $header;
        }

        return new \ArrayIterator($headers);
    }

    /**
     * Returns mock of Message object with injected headers
     *
     * @param array $headers headers array which will be injected into message mock
     * @return Message
     */
    private function getMessageMock(array $headers)
    {
        $msg = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Storage\Message')
            ->disableOriginalConstructor()
            ->getMock();
        $messageHeaders = $this->getMockBuilder('Zend\Mail\Headers')
            ->disableOriginalConstructor()
            ->getMock();
        $msg->expects($this->once())
            ->method('getHeaders')
            ->will($this->returnValue($messageHeaders));
        $messageHeaders->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($headers));

        return $msg;
    }
}
