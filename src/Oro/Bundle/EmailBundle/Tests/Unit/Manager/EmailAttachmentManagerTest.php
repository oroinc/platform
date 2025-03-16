<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailAttachmentManagerTest extends TestCase
{
    private FileManager&MockObject $fileManager;
    private ManagerRegistry&MockObject $doctrine;
    private RouterInterface&MockObject $router;
    private ConfigFileValidator&MockObject $configFileValidator;
    private AttachmentAssociationHelper&MockObject $attachmentAssociationHelper;
    private EntityManagerInterface&MockObject $em;
    private EmailAttachmentManager $emailAttachmentManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->configFileValidator = $this->createMock(ConfigFileValidator::class);
        $this->attachmentAssociationHelper = $this->createMock(AttachmentAssociationHelper::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->doctrine->expects(self::any())
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
        $content->expects(self::any())
            ->method('getContent')
            ->willReturn('content');
        $content->expects(self::any())
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
        $emailAttachment->expects(self::any())
            ->method('getContent')
            ->willReturn($this->getContentMock());
        $emailAttachment->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        return $emailAttachment;
    }

    public function testLinkEmailAttachmentToTargetEntityNotValid(): void
    {
        $file = $this->getMockBuilder(File::class)
            ->onlyMethods(['getFilename'])
            ->getMock();
        $countable = $this->createMock(ConstraintViolationList::class);
        $countable->expects(self::never())
            ->method('count')
            ->willReturn(2);

        $this->configFileValidator->expects(self::never())
            ->method('validate')
            ->willReturn($countable);

        $emailAttachment = $this->getEmailAttachment();

        $emailAttachment->expects(self::any())
            ->method('getFile')
            ->willReturn($file);

        $this->emailAttachmentManager->linkEmailAttachmentToTargetEntity($emailAttachment, new SomeEntity());
    }

    public function testGetResizedImageUrl(): void
    {
        $emailAttachment = $this->getEmailAttachment();

        $this->router->expects(self::once())
            ->method('generate')
            ->with(
                'oro_resize_email_attachment',
                ['width' => 100, 'height' => 50, 'id' => 1]
            );

        $this->emailAttachmentManager->getResizedImageUrl($emailAttachment, 100, 50);
    }
}
