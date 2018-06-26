<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityExtendBundle\Form\EventListener\EnumFieldConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\Form\Extension\EnumFieldConfigExtension;

class EnumFieldConfigExtensionTest extends \PHPUnit\Framework\TestCase
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
        $this->assertEquals(ConfigType::class, $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        $builder = $this->createMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber);

        $this->extension->buildForm($builder, []);
    }
}
