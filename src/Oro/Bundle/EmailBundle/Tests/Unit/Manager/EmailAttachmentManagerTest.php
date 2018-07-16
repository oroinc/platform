<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Gaufrette\Filesystem;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class EmailAttachmentManagerTest
 *
 * @package Oro\Bundle\EmailBundle\Tests\Unit\Manager
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailAttachmentManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailAttachmentManager
     */
    protected $emailAttachmentManager;

    /**
     * @var FileManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fileManager;

    /**
     * @var EmailCacheManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $emailCacheManager;

    /**
     * @var Filesystem|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $filesystem;

    /**
     * @var Registry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ConfigFileValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configFileValidator;

    /** @var  \PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /**
     * @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $om;

    /**
     * @var EmailActivityListProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $activityListProvider;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $em;

    /**
     * @var Attachment|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $attachment;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $attachmentAssociationHelper;

    protected function setUp()
    {
        $this->fileManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\FileManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailCacheManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Cache\EmailCacheManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configFileValidator = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->om));

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachment = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Entity\Attachment')
            ->setMethods(['supportTarget', 'setFile'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentAssociationHelper = $this
            ->getMockBuilder('Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAttachmentManager = new EmailAttachmentManager(
            $this->fileManager,
            $this->em,
            $this->router,
            $this->configFileValidator,
            $this->attachmentAssociationHelper
        );
    }

    public function testLinkEmailAttachmentToTargetEntity()
    {
        $emailAttachment = new EmailAttachment();
        ReflectionUtil::setId($emailAttachment, 1);
        $emailAttachment->setContent($this->getContentMock());

        $file = new ComponentFile(__DIR__ . '/../Fixtures/attachment/test.txt');

        $this->fileManager->expects($this->once())
            ->method('writeToTemporaryFile')
            ->with(
                ContentDecoder::decode(
                    $emailAttachment->getContent()->getContent(),
                    $emailAttachment->getContent()->getContentTransferEncoding()
                )
            )
            ->willReturn($file);
        $this->configFileValidator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock('Countable'));

        $this->emailAttachmentManager->linkEmailAttachmentToTargetEntity($emailAttachment, new SomeEntity());

        $expectedFile = new UploadedFile(
            $file->getPathname(),
            $emailAttachment->getFileName(),
            $emailAttachment->getContentType()
        );
        $this->assertEquals($expectedFile, $emailAttachment->getFile()->getFile());
    }

    public function testLinkEmailAttachmentToTargetEntityNotValid()
    {
        $file = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Entity\File')
            ->setMethods(['getFilename'])
            ->getMock();
        $countable = $this->getMockBuilder('Countable')->getMock();
        $countable->expects($this->never())
            ->method('count')
            ->will($this->returnValue(2));

        $this->configFileValidator->expects($this->never())
            ->method('validate')
            ->will($this->returnValue($countable));

        $emailAttachment = $this->getEmailAttachment();

        $emailAttachment->expects($this->any())
            ->method('getFile')
            ->will($this->returnValue($file));

        $this->emailAttachmentManager->linkEmailAttachmentToTargetEntity($emailAttachment, new SomeEntity());
    }

    public function testGetResizedImageUrl()
    {
        $emailAttachment = $this->getEmailAttachment();

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'oro_resize_email_attachment',
                [
                    'width' => 100,
                    'height' => 50,
                    'id' => 1,
                ]
            );
        $this->emailAttachmentManager->getResizedImageUrl($emailAttachment, 100, 50);
    }

    protected function getContentMock()
    {
        $content = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent')
            ->getMock();
        $content->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue('content'));
        $content->expects($this->any())
            ->method('getContentTransferEncoding')
            ->will($this->returnValue('base64'));

        return $content;
    }

    protected function getEmailAttachment()
    {
        $emailAttachment = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailAttachment')
            ->setMethods(['getContent', 'setFile', 'getFile', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $emailAttachment->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($this->getContentMock()));
        $emailAttachment->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        return $emailAttachment;
    }

    /**
     * @param EmailAttachment $emailAttachment
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getEmailMock(EmailAttachment $emailAttachment)
    {
        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->setMethods(['getEmailBody', 'getAttachments'])
            ->disableOriginalConstructor()
            ->getMock();
        $email->expects($this->any())
            ->method('getEmailBody')
            ->will($this->returnValue($email));
        $email->expects($this->any())
            ->method('getAttachments')
            ->will($this->returnValue([$emailAttachment]));

        return $email;
    }
}
