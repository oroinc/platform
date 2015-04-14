<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormEvent;

use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentType;

class EmailAttachmentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailAttachmentType
     */
    protected $emailAttachmentType;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;
    
    protected $filesystem;

    protected $filesystemMap;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystemMap = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem = $this->getMockBuilder('Gaufrette\Filesystem')
            ->setMethods(['delete', 'write', 'has'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystemMap->expects($this->any())
            ->method('get')
            ->with('attachments')
            ->will($this->returnValue($this->filesystem));

        $this->emailAttachmentType = new EmailAttachmentType($this->em, $this->filesystemMap);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_attachment', $this->emailAttachmentType->getName());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'         => 'Oro\Bundle\EmailBundle\Form\Model\EmailAttachment',
                    'intention'          => 'email_attachment',
                ]
            );

        $type = new EmailAttachmentType($this->em, $this->filesystemMap);
        $type->setDefaultOptions($resolver);
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->exactly(3))
            ->method('add');

        $builder->expects($this->once())
            ->method('addEventListener');

        $this->emailAttachmentType->buildForm($builder, []);
    }

    public function testInitAttachmentEntityNew()
    {
        $fileContent = "test attachment\n";
        $attachment = new EmailAttachment();

        $uploadedFile = $this
            ->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')
            ->enableOriginalConstructor()
            ->setConstructorArgs([__DIR__ . '/../../Fixtures/attachment/test.txt', ''])
            ->getMock();

        $uploadedFile->expects($this->once())
            ->method('getMimeType')
            ->willReturn('text/plain');

        $uploadedFile->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn('test.txt');

        $uploadedFile->expects($this->once())
            ->method('getRealPath')
            ->willReturn(__DIR__ . '/../../Fixtures/attachment/test.txt');

        $attachment->setFile($uploadedFile);
        $attachment->setType(EmailAttachment::TYPE_UPLOADED);
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $formEvent = new FormEvent($form, $attachment);
        $this->emailAttachmentType->initAttachmentEntity($formEvent);

        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Entity\EmailAttachment', $attachment->getEmailAttachment());
        $content = $attachment->getEmailAttachment()->getContent();
        $this->assertEquals(base64_encode($fileContent), $content->getContent());
        $this->assertEquals('base64', $content->getContentTransferEncoding());

        $this->assertEquals($attachment->getEmailAttachment()->getContentType(), 'text/plain');
        $this->assertEquals($attachment->getFileName(), 'test.txt');
    }

    public function testInitAttachmentEntityNonExisting()
    {
        $attachment = null;

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $formEvent = new FormEvent($form, $attachment);
        $this->emailAttachmentType->initAttachmentEntity($formEvent);

        $this->assertEquals($formEvent->getData(), null);
    }
}
