<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileConfigType;

class FileConfigTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var FileConfigType */
    protected $type;

    public function setUp()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new FileConfigType($configManager);
    }

    public function testInterface()
    {
        $this->assertSame('oro_attachment_file_config', $this->type->getName());
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with('form.post_bind');

        $options = [];
        $this->type->buildForm($builder, $options);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'mapped' => false,
                    'label'  => false
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }
}
