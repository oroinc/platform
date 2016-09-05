<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestSubscriber;

class FileTypeTest extends \PHPUnit_Framework_TestCase
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
        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($event);

        $builder->expects($this->once())
            ->method('add')
            ->with('file', 'file');

        $options = ['checkEmptyFile' => true];
        $this->type->buildForm($builder, $options);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Oro\Bundle\AttachmentBundle\Entity\File',
                    'checkEmptyFile' => false,
                    'allowDelete' => true
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }
}
