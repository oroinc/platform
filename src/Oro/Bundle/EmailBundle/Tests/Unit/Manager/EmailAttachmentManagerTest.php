<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailAttachmentManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var ConfigFileValidator|\PHPUnit\Framework\MockObject\MockObject */
    private $configFileValidator;

    /** @var AttachmentAssociationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentAssociationHelper;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EmailAttachmentManager */
    private $emailAttachmentManager;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->configFileValidator = $this->createMock(ConfigFileValidator::class);
        $this->attachmentAssociationHelper = $this->createMock(AttachmentAssociationHelper::class);
        $this->em = $this->createMock(EntityManager::class);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Attachment::class)
            ->willReturn($this->em);

        $this->emailAttachmentManager = new EmailAttachmentManager(
            $this->fileManager,
            $this->doctrine,
            $this->router,
            $this->configFileValidator,
            $this->attachmentAssociationHelper
        );
    }

    private function getContentMock()
    {
        $content = $this->createMock(EmailAttachmentContent::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn('content');
        $content->expects($this->any())
            ->method('getContentTransferEncoding')
            ->willReturn('base64');

        return $content;
    }

    private function getEmailAttachment()
    {
        $emailAttachment = $this->getMockBuilder(EmailAttachment::class)
            ->onlyMethods(['getContent', 'setFile', 'getFile', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $emailAttachment->expects($this->any())
            ->method('getContent')
            ->willReturn($this->getContentMock());
        $emailAttachment->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        return $emailAttachment;
    }

    public function testLinkEmailAttachmentToTargetEntityNotValid()
    {
        $file = $this->getMockBuilder(File::class)
            ->onlyMethods(['getFilename'])
            ->getMock();
        $countable = $this->createMock(ConstraintViolationList::class);
        $countable->expects($this->never())
            ->method('count')
            ->willReturn(2);

        $this->configFileValidator->expects($this->never())
            ->method('validate')
            ->willReturn($countable);

        $emailAttachment = $this->getEmailAttachment();

        $emailAttachment->expects($this->any())
            ->method('getFile')
            ->willReturn($file);

        $this->emailAttachmentManager->linkEmailAttachmentToTargetEntity($emailAttachment, new SomeEntity());
    }

    public function testGetResizedImageUrl()
    {
        $emailAttachment = $this->getEmailAttachment();

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'oro_resize_email_attachment',
                ['width' => 100, 'height' => 50, 'id' => 1]
            );

        $this->emailAttachmentManager->getResizedImageUrl($emailAttachment, 100, 50);
    }
}
