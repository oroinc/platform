<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormEvent;

use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentType;
use Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer;

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

    /**
     * @var EmailAttachmentTransformer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailAttachmentTransformer;


    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAttachmentTransformer = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAttachmentType = new EmailAttachmentType($this->em, $this->emailAttachmentTransformer);
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

        $type = new EmailAttachmentType($this->em, $this->emailAttachmentTransformer);
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

    /**
     * @param $type
     * @param $getRepositoryCalls
     * @param $getRepositoryArgument
     * @param $repoReturnObject
     * @param $oroToEntityCalls
     * @param $entityFromUploadedFileCalls
     *
     * @dataProvider attachmentProvider
     */
    public function testInitAttachmentEntity(
        $type,
        $getRepositoryCalls,
        $getRepositoryArgument,
        $repoReturnObject,
        $oroToEntityCalls,
        $entityFromUploadedFileCalls
    ) {
        $attachment = $this->getMock('Oro\Bundle\EmailBundle\Form\Model\EmailAttachment');
        $attachment->expects($this->once())
            ->method('setEmailAttachment');

        $uploadedFile = $this
            ->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')
            ->enableOriginalConstructor()
            ->setConstructorArgs([__DIR__ . '/../../Fixtures/attachment/test.txt', ''])
            ->getMock();

        $attachment->expects($this->any())
            ->method('getFile')
            ->willReturn($uploadedFile);

        $formEvent = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $formEvent->expects($this->once())
            ->method('getData')
            ->willReturn($attachment);

        $attachment->expects($this->once())
            ->method('getEmailAttachment')
            ->willReturn(false);

        $attachment->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        if ($getRepositoryCalls) {
            $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->getMock();

            $this->em->expects($this->exactly($getRepositoryCalls))
                ->method('getRepository')
                ->with($getRepositoryArgument)
                ->willReturn($repo);

            $repo->expects($this->once())
                ->method('find')
                ->willReturn($repoReturnObject);
        }

        $this->emailAttachmentTransformer->expects($this->exactly($oroToEntityCalls))
            ->method('oroToEntity');

        $this->emailAttachmentTransformer->expects($this->exactly($entityFromUploadedFileCalls))
            ->method('entityFromUploadedFile');

        $this->emailAttachmentType->initAttachmentEntity($formEvent);
    }

    /**
     * @return array
     */
    public function attachmentProvider()
    {
        return [
            [
                'type' => EmailAttachment::TYPE_ATTACHMENT,
                'getRepositoryCalls' => 1,
                'getRepositoryArgument' => 'OroAttachmentBundle:Attachment',
                'repoReturnObject' => $this->getMock('Oro\Bundle\AttachmentBundle\Entity\Attachment'),
                'oroToEntityCalls' => 1,
                'entityFromUploadedFileCalls' => 0,
            ],
            [
                'type' => EmailAttachment::TYPE_EMAIL_ATTACHMENT,
                'getRepositoryCalls' => 1,
                'getRepositoryArgument' => 'OroEmailBundle:EmailAttachment',
                'repoReturnObject' => $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailAttachment'),
                'oroToEntityCalls' => 0,
                'entityFromUploadedFileCalls' => 0,
            ],
            [
                'type' => EmailAttachment::TYPE_UPLOADED,
                'getRepositoryCalls' => 0,
                'getRepositoryArgument' => null,
                'repoReturnObject' => null,
                'oroToEntityCalls' => 0,
                'entityFromUploadedFileCalls' => 1,
            ],
        ];
    }
}
