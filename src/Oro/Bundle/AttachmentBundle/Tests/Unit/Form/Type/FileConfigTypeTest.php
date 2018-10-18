<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileConfigType;
use Symfony\Component\Form\FormEvents;

class FileConfigTypeTest extends \PHPUnit\Framework\TestCase
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
        $builder = $this->createMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT);

        $options = [];
        $this->type->buildForm($builder, $options);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'mapped' => false,
                    'label'  => false
                ]
            );

        $this->type->configureOptions($resolver);
    }
}
