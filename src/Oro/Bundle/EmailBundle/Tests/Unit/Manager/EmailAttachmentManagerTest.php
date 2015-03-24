<?php

namespace Oro\src\Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;

use Gaufrette\Filesystem;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\Attachment;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

/**
 * Class EmailAttachmentManagerTest
 *
 * @package Oro\src\Oro\Bundle\EmailBundle\Tests\Unit\Manager
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailAttachmentManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailAttachmentManager
     */
    protected $emailAttachmentManager;

    /**
     * @var EmailCacheManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailCacheManager;

    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filesystem;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ConfigFileValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configFileValidator;

    /**
     * @var KernelInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $kernel;

    /**
     * @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacadeLink;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    protected function setUp()
    {
        $this->emailCacheManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Cache\EmailCacheManager')
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMap = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem = $this->getMockBuilder('Gaufrette\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMap->expects($this->once())
            ->method('get')
            ->with('attachments')
            ->will($this->returnValue($this->filesystem));

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configFileValidator = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $this->kernel->expects($this->once())
            ->method('getRootDir')
            ->willReturn('');

        $this->em = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->em));

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->setMethods(['getLoggedUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacadeLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->setMethods(['getService'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacadeLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->securityFacade));

        $this->emailAttachmentManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager')
            ->setMethods(['getAttachmentFullPath'])
            ->setConstructorArgs([
                $this->emailCacheManager,
                $filesystemMap,
                $this->registry,
                $this->configFileValidator,
                $this->kernel,
                $this->securityFacadeLink
            ])
            ->getMock();

        $this->configFileValidator->expects($this->any())
            ->method('validate')
            ->willReturn($this->getMock('Countable'));

        $this->emailAttachmentManager->expects($this->any())
            ->method('getAttachmentFullPath')
            ->willReturn(__DIR__ . '/../Fixtures/attachment/test.txt');
    }

    public function testLinkEmailAttachmentsToEntity()
    {
        $email = $this->createEmailEntity();

        $this->emailCacheManager->expects($this->once())
            ->method('ensureEmailBodyCached')
            ->with($email);

        $this->configFileValidator->expects($this->exactly(2))
            ->method('validate');

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->emailAttachmentManager->linkEmailAttachmentsToEntity($email, new SomeEntity());
    }

    /**
     * @return Email
     */
    protected function createEmailEntity()
    {
        $email = new Email();
        $emailBody = new EmailBody();

        $email->setEmailBody($emailBody);

        $attachment1 = new EmailAttachment();
        $attachment1->setFileName('attachment1');
        $attachment1->setContentType('allowed_content_type');
        $attachment1->setEmailBody($emailBody);
        $attachment1->setAttachment(new Attachment());
        $emailBody->addAttachment($attachment1);

        $content1 = new EmailAttachmentContent();
        $content1->setContent(base64_encode('content1'));
        $content1->setContentTransferEncoding('base64');
        $attachment1->setContent($content1);

        $attachment2 = new EmailAttachment();
        $attachment2->setFileName('attachment2');
        $attachment2->setContentType('disallowed_content_type');
        $attachment2->setEmailBody($emailBody);
        $attachment2->setAttachment(new Attachment());
        $emailBody->addAttachment($attachment2);

        $content2 = new EmailAttachmentContent();
        $content2->setContent(base64_encode('content2'));
        $content2->setContentTransferEncoding('base64');
        $attachment2->setContent($content2);

        return $email;
    }
}
