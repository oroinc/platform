<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityExtendBundle\Form\Extension\EnumFieldConfigExtension;
use Oro\Bundle\EntityExtendBundle\Form\EventListener\EnumFieldConfigSubscriber;

class EnumFieldConfigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var EnumFieldConfigExtension */
    protected $extension;

    /** @var EnumFieldConfigSubscriber */
    protected $subscriber;

    public function setUp()
    {
        $this->subscriber = $this->getMockBuilder(EnumFieldConfigSubscriber::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new EnumFieldConfigExtension($this->subscriber);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals('oro_entity_config_type', $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber);

        $this->extension->buildForm($builder, []);
    }
}
