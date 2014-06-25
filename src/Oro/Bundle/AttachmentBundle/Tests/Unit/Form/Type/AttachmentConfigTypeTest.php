<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;


use Oro\Bundle\AttachmentBundle\Form\Type\AttachmentConfigType;

class AttachmentConfigTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttachmentConfigType */
    protected $type;

    public function setUp()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new AttachmentConfigType($configManager);
    }

    public function testInterface()
    {
        $this->assertSame('oro_attachment_config', $this->type->getName());
        $this->assertSame('form', $this->type->getParent());
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
