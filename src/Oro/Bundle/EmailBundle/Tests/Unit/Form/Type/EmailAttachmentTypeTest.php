<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentType;
use Symfony\Component\Form\FormEvent;

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

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAttachmentType = new EmailAttachmentType($this->em);
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
                    'data_class'         => 'Oro\Bundle\EmailBundle\Entity\EmailAttachment',
                    'intention'          => 'email_attachment',
                ]
            );

        $type = new EmailAttachmentType($this->em);
        $type->setDefaultOptions($resolver);
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->exactly(2))
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
            ->setConstructorArgs([tempnam(sys_get_temp_dir(), ''), 'dummy'])
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

        $attachment->setUploadedFile($uploadedFile);
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $formEvent = new FormEvent($form, $attachment);
        $this->emailAttachmentType->initAttachmentEntity($formEvent);

        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent', $attachment->getContent());
        $content = $attachment->getContent();
        $this->assertEquals(base64_encode($fileContent), $content->getContent());
        $this->assertEquals('base64', $content->getContentTransferEncoding());

        $this->assertEquals($attachment->getContentType(), 'text/plain');
        $this->assertEquals($attachment->getFileName(), 'test.txt');
    }

    public function testInitAttachmentEntityExisting()
    {
        $attachment = new EmailAttachment();
        $id = 1;

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($attachment);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailAttachment')
            ->willReturn($repo);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form2 = $this->getMock('Symfony\Component\Form\FormInterface');
        $form2->expects($this->once())
            ->method('getData')
            ->willReturn($id);

        $form->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn($form2);

        $formEvent = new FormEvent($form, null);
        $this->emailAttachmentType->initAttachmentEntity($formEvent);

        $this->assertEquals($formEvent->getData(), $attachment);
    }
}
