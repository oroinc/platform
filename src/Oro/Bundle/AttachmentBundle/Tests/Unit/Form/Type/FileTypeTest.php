<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestSubscriber;
use Symfony\Component\Form\Extension\Core\Type\FileType as SymfonyFileType;

class FileTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileType */
    protected $type;

    public function setUp()
    {
        $this->type = new FileType();
    }

    public function testInterface()
    {
        $this->assertSame('oro_file', $this->type->getName());
    }

    public function testBuildForm()
    {
        $event = new TestSubscriber();
        $this->type->setEventSubscriber($event);
        $builder = $this->createMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($event);

        $builder->expects($this->once())
            ->method('add')
            ->with('file', SymfonyFileType::class);

        $options = [
            'checkEmptyFile' => true,
            'addEventSubscriber' => true
        ];
        $this->type->buildForm($builder, $options);
    }

    public function testBuildFormWithoutEventSubscriber()
    {
        $builder = $this->createMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $builder->expects($this->once())
            ->method('add')
            ->with('file', SymfonyFileType::class);

        $options = [
            'checkEmptyFile' => true,
            'addEventSubscriber' => false
        ];
        $this->type->buildForm($builder, $options);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Oro\Bundle\AttachmentBundle\Entity\File',
                    'checkEmptyFile' => false,
                    'allowDelete' => true,
                    'addEventSubscriber' => true
                ]
            );

        $this->type->configureOptions($resolver);
    }
}
