<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentsType;

class EmailAttachmentsTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailAttachmentsType
     */
    protected $emailAttachmentsType;

    protected function setUp()
    {
        $this->emailAttachmentsType = new EmailAttachmentsType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_attachments', $this->emailAttachmentsType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('collection', $this->emailAttachmentsType->getParent());
    }

    public function testSanitizeAttachmentsWithCorrectExistAttachments()
    {
        $attachment = new EmailAttachment();
        $attachment->setId(1);

        $attachments = new ArrayCollection(['first' => $attachment]);
        $event = new FormEvent($this->createMock(FormInterface::class), $attachments);

        $this->emailAttachmentsType->sanitizeAttachments($event);

        /** @var ArrayCollection $resultData */
        $resultData = $event->getData();
        $this->assertEquals(1, $resultData->count());
        $resultAttachment = $resultData->current();
        $this->assertEquals($attachment, $resultAttachment);
    }

    public function testSanitizeAttachmentsWithCorrectNewAttachments()
    {
        $attachment = new EmailAttachment();
        $attachments = new ArrayCollection(['first' => $attachment]);
        $event = new FormEvent($this->createMock(FormInterface::class), $attachments);

        $this->emailAttachmentsType->sanitizeAttachments($event);

        /** @var ArrayCollection $resultData */
        $resultData = $event->getData();
        $this->assertEquals(1, $resultData->count());
        $resultAttachment = $resultData->current();
        $this->assertEquals($attachment, $resultAttachment);
    }

    public function testSanitizeAttachmentsWithNonCorrectAttachment()
    {
        $attachment = null;
        $attachments = new ArrayCollection(['first' => $attachment]);
        $event = new FormEvent($this->createMock(FormInterface::class), $attachments);

        $this->emailAttachmentsType->sanitizeAttachments($event);

        /** @var ArrayCollection $resultData */
        $resultData = $event->getData();
        $this->assertEquals(0, $resultData->count());
    }
}
