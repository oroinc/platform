<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager\DTO;

use Oro\Bundle\ImapBundle\Mail\Storage\Body;
use Oro\Bundle\ImapBundle\Mail\Storage\Exception\InvalidBodyFormatException;
use Oro\Bundle\ImapBundle\Manager\DTO\Email;
use Oro\Bundle\ImapBundle\Manager\DTO\ItemId;
use Oro\Bundle\ImapBundle\Manager\DTO\EmailBody;
use Oro\Bundle\ImapBundle\Manager\DTO\EmailAttachment;
use Zend\Mail\Header\ContentType;

class EmailTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGettersAndSetters()
    {
        $message = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Storage\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $id = new ItemId('testId', 'testChangeKey');
        $sentAt = new \DateTime('now');
        $receivedAt = new \DateTime('now');
        $internalDate = new \DateTime('now');
        $flags = ["\\Test"];

        $obj = new Email($message);
        $obj
            ->setId($id)
            ->setSubject('testSubject')
            ->setFrom('testFrom')
            ->addToRecipient('testToRecipient')
            ->addCcRecipient('testCcRecipient')
            ->addBccRecipient('testBccRecipient')
            ->setSentAt($sentAt)
            ->setReceivedAt($receivedAt)
            ->setInternalDate($internalDate)
            ->setImportance(1)
            ->setMessageId('testMessageId')
            ->setMultiMessageId(['testMessageId1','testMessageId2'])
            ->setXMessageId('testXMessageId')
            ->setXThreadId('testXThreadId');

        $this->assertEquals($id, $obj->getId());
        $this->assertEquals('testSubject', $obj->getSubject());
        $this->assertEquals('testFrom', $obj->getFrom());
        $toRecipients = $obj->getToRecipients();
        $this->assertEquals('testToRecipient', $toRecipients[0]);
        $ccRecipients = $obj->getCcRecipients();
        $this->assertEquals('testCcRecipient', $ccRecipients[0]);
        $bccRecipients = $obj->getBccRecipients();
        $this->assertEquals('testBccRecipient', $bccRecipients[0]);
        $this->assertEquals($sentAt, $obj->getSentAt());
        $this->assertEquals($receivedAt, $obj->getReceivedAt());
        $this->assertEquals($internalDate, $obj->getInternalDate());
        $this->assertEquals(1, $obj->getImportance());
        $this->assertEquals('testMessageId', $obj->getMessageId());
        $this->assertEquals('testXMessageId', $obj->getXMessageId());
        $this->assertEquals('testXThreadId', $obj->getXThreadId());
        $this->assertCount(2, $obj->getMultiMessageId());
        $this->assertInternalType('array', $obj->getMultiMessageId());

        $contentType = new ContentType();
        $contentType->setType('text/plain');
        $message->expects($this->once())
            ->method('getPriorContentType')
            ->will($this->returnValue($contentType));

        $srcBodyContent = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Storage\Content')
            ->disableOriginalConstructor()
            ->getMock();
        $srcBody = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Storage\Body')
            ->disableOriginalConstructor()
            ->getMock();
        $srcBody->expects($this->at(0))
            ->method('getContent')
            ->with($this->equalTo(Body::FORMAT_TEXT))
            ->will($this->returnValue($srcBodyContent));
        $srcBodyContent->expects($this->once())
            ->method('getDecodedContent')
            ->will($this->returnValue('testContent'));
        $body = new EmailBody();
        $body->setContent('testContent')->setBodyIsText(true);
        $message->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue($srcBody));
        $this->assertEquals($body, $obj->getBody());
        $message->expects($this->exactly(2))
            ->method('getFlags')
            ->will($this->returnValue($flags));
        $this->assertTrue($obj->hasFlag("\\Test"));
        $this->assertFalse($obj->hasFlag("\\Test2"));

        $srcAttachmentContent = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Storage\Content')
            ->disableOriginalConstructor()
            ->getMock();
        $srcAttachmentFileName = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Storage\Value')
            ->disableOriginalConstructor()
            ->getMock();
        $srcAttachment = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Storage\Attachment')
            ->disableOriginalConstructor()
            ->getMock();
        $srcAttachment->expects($this->once())
            ->method('getFileName')
            ->will($this->returnValue($srcAttachmentFileName));
        $srcAttachment->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($srcAttachmentContent));
        $srcAttachmentFileName->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('fileName'));
        $srcAttachmentContent->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue('content'));
        $srcAttachmentContent->expects($this->once())
            ->method('getContentType')
            ->will($this->returnValue('contentType'));
        $srcAttachmentContent->expects($this->once())
            ->method('getContentTransferEncoding')
            ->will($this->returnValue('contentTransferEncoding'));
        $attachment = new EmailAttachment();
        $attachment
            ->setFileName('fileName')
            ->setContent('content')
            ->setContentType('contentType')
            ->setContentTransferEncoding('contentTransferEncoding');
        $message->expects($this->once())
            ->method('getAttachments')
            ->will($this->returnValue(array($srcAttachment)));
        $attachments = $obj->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertEquals($attachment, $attachments[0]);
    }
}
