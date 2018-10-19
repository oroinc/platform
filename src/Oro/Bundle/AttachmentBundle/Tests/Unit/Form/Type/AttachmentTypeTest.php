<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\AttachmentType;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class AttachmentTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentType */
    protected $attachmentType;

    public function setUp()
    {
        $this->attachmentType = new AttachmentType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Oro\Bundle\AttachmentBundle\Entity\Attachment',
                    'parentEntityClass' => '',
                    'checkEmptyFile' => false,
                    'allowDelete' => true
                ]
            );

        $this->attachmentType->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        $builder = $this->createMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->at(0))
            ->method('add')
            ->with('file', FileType::class);

        $builder->expects($this->at(1))
            ->method('add')
            ->with('comment', TextareaType::class);

        $this->attachmentType->buildForm($builder, ['checkEmptyFile' => true, 'allowDelete' => true]);
    }
}
