<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\AttachmentType;

class AttachmentTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttachmentType */
    protected $attachmentType;

    public function setUp()
    {
        $this->attachmentType = new AttachmentType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_attachment', $this->attachmentType->getName());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Oro\Bundle\AttachmentBundle\Entity\Attachment',
                    'cascade_validation' => true,
                    'parentEntityClass' => '',
                    'checkEmptyFile' => false,
                    'allowDelete' => true
                ]
            );

        $this->attachmentType->setDefaultOptions($resolver);
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->at(0))
            ->method('add')
            ->with('file', 'oro_file');

        $builder->expects($this->at(1))
            ->method('add')
            ->with('comment', 'textarea');

        $this->attachmentType->buildForm($builder, ['checkEmptyFile' => true, 'allowDelete' => true]);
    }
}
