<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment as EmailAttachmentEntity;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentType;
use Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailAttachmentTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EmailAttachmentTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAttachmentTransformer;

    /** @var EmailAttachmentType */
    private $emailAttachmentType;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->emailAttachmentTransformer = $this->createMock(EmailAttachmentTransformer::class);

        $this->emailAttachmentType = new EmailAttachmentType($this->doctrine, $this->emailAttachmentTransformer);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'    => EmailAttachment::class,
                    'csrf_token_id' => 'email_attachment',
                ]
            );

        $type = new EmailAttachmentType($this->doctrine, $this->emailAttachmentTransformer);
        $type->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(3))
            ->method('add');
        $builder->expects($this->once())
            ->method('addEventListener');

        $this->emailAttachmentType->buildForm($builder, []);
    }

    /**
     * @dataProvider attachmentProvider
     */
    public function testInitAttachmentEntity(
        $type,
        $getRepositoryCalls,
        $getRepositoryArgument,
        $repoReturnObject,
        $attachmentEntityToEntityCalls,
        $entityFromUploadedFileCalls
    ) {
        $attachment = $this->createMock(EmailAttachment::class);
        $attachment->expects($this->once())
            ->method('setEmailAttachment');

        $uploadedFile = $this->getMockBuilder(UploadedFile::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([__DIR__ . '/../../Fixtures/attachment/test.txt', ''])
            ->getMock();

        $attachment->expects($this->any())
            ->method('getFile')
            ->willReturn($uploadedFile);

        $formEvent = $this->createMock(FormEvent::class);
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
            $repo = $this->createMock(EntityRepository::class);
            $this->doctrine->expects($this->exactly($getRepositoryCalls))
                ->method('getRepository')
                ->with($getRepositoryArgument)
                ->willReturn($repo);
            $repo->expects($this->once())
                ->method('find')
                ->willReturn($repoReturnObject);
        }

        $this->emailAttachmentTransformer->expects($this->exactly($attachmentEntityToEntityCalls))
            ->method('attachmentEntityToEntity');

        $this->emailAttachmentTransformer->expects($this->exactly($entityFromUploadedFileCalls))
            ->method('entityFromUploadedFile');

        $this->emailAttachmentType->initAttachmentEntity($formEvent);
    }

    public function attachmentProvider(): array
    {
        return [
            [
                'type' => EmailAttachment::TYPE_ATTACHMENT,
                'getRepositoryCalls' => 1,
                'getRepositoryArgument' => Attachment::class,
                'repoReturnObject' => $this->createMock(Attachment::class),
                'attachmentEntityToEntityCalls' => 1,
                'entityFromUploadedFileCalls' => 0,
            ],
            [
                'type' => EmailAttachment::TYPE_EMAIL_ATTACHMENT,
                'getRepositoryCalls' => 1,
                'getRepositoryArgument' => EmailAttachmentEntity::class,
                'repoReturnObject' => $this->createMock(EmailAttachment::class),
                'attachmentEntityToEntityCalls' => 0,
                'entityFromUploadedFileCalls' => 0,
            ],
            [
                'type' => EmailAttachment::TYPE_UPLOADED,
                'getRepositoryCalls' => 0,
                'getRepositoryArgument' => null,
                'repoReturnObject' => null,
                'attachmentEntityToEntityCalls' => 0,
                'entityFromUploadedFileCalls' => 1,
            ],
        ];
    }
}
