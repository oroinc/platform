<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailAttachmentProvider;
use Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer;

class EmailAttachmentProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailThreadProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $emailThreadProvider;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $em;

    /**
     * @var AttachmentProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $attachmentProvider;

    /**
     * @var EmailAttachmentTransformer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $emailAttachmentTransformer;

    /**
     * @var EmailAttachmentProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $emailAttachmentProvider;

    protected function setUp()
    {
        $this->emailThreadProvider = $this->createMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider');

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentProvider = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAttachmentTransformer = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAttachmentProvider = new EmailAttachmentProvider(
            $this->emailThreadProvider,
            $this->em,
            $this->attachmentProvider,
            $this->emailAttachmentTransformer
        );
    }

    /**
     * @param $threadEmails
     * @param $transformationCalls
     *
     * @dataProvider threadEmailsProvider
     */
    public function testGetThreadAttachments($threadEmails, $transformationCalls)
    {
        $emailEntity = $this->createMock('Oro\Bundle\EmailBundle\Entity\Email');

        $this->emailThreadProvider->expects($this->once())
            ->method('getThreadEmails')
            ->with($this->em, $emailEntity)
            ->willReturn($threadEmails);

        $this->emailAttachmentTransformer->expects($this->exactly($transformationCalls))
            ->method('entityToModel');

        $result = $this->emailAttachmentProvider->getThreadAttachments($emailEntity);
        $this->assertTrue(is_array($result));
        $this->assertEquals($transformationCalls, sizeof($result));
    }

    /**
     * @return array
     */
    public function threadEmailsProvider()
    {
        $threadEmails = [];
        $transformationCalls = 0;
        $threadEmailCount = 3;
        for ($i = 0; $i < $threadEmailCount; $i++) {
            $emailBody = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailBody');
            $emailBody->expects($this->once())
                ->method('getHasAttachments')
                ->willReturn(true);

            $attachments = $this->getAttachments($i + 1);
            $transformationCalls += $i + 1;

            $emailBody->expects($this->once())
                ->method('getAttachments')
                ->willReturn($attachments);

            $threadEmail = $this->createMock('Oro\Bundle\EmailBundle\Entity\Email');
            $threadEmail->expects($this->exactly($threadEmailCount))
                ->method('getEmailBody')
                ->willReturn($emailBody);

            $threadEmails[] = $threadEmail;
        }

        return [
            [
                'threadEmails' => $threadEmails,
                'transformationCalls' => $transformationCalls,
            ],
        ];
    }

    public function testGetScopeEntityAttachments()
    {
        $entity = $this->createMock('\stdClass');

        $oroAttachments = [];
        $size = 3;
        for ($i = 0; $i < $size; $i++) {
            $oroAttachments[] = $this->createMock('Oro\Bundle\AttachmentBundle\Entity\Attachment');
        }

        $this->attachmentProvider->expects($this->once())
            ->method('getEntityAttachments')
            ->with($entity)
            ->willReturn($oroAttachments);

        $this->emailAttachmentTransformer->expects($this->exactly($size))
            ->method('oroToModel');

        $result = $this->emailAttachmentProvider->getScopeEntityAttachments($entity);
        $this->assertTrue(is_array($result));
        $this->assertEquals($size, sizeof($result));
    }

    /**
     * @param int $count
     *
     * @return array
     */
    protected function getAttachments($count)
    {
        $attachments = [];
        for ($i = 0; $i < $count; $i++) {
            $attachments[] = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailAttachment');
        }

        return $attachments;
    }
}
