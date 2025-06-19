<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Form\Type\AttachmentType;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttachmentTypeTest extends TestCase
{
    private AttachmentType $attachmentType;

    #[\Override]
    protected function setUp(): void
    {
        $this->attachmentType = new AttachmentType();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => Attachment::class,
                    'parentEntityClass' => '',
                    'checkEmptyFile' => false,
                    'allowDelete' => true
                ]
            );

        $this->attachmentType->configureOptions($resolver);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                ['file', FileType::class],
                ['comment', TextareaType::class]
            )
            ->willReturnSelf();

        $this->attachmentType->buildForm($builder, ['checkEmptyFile' => true, 'allowDelete' => true]);
    }
}
