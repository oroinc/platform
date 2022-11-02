<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityExtendBundle\Form\EventListener\EnumFieldConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\Form\Extension\EnumFieldConfigExtension;
use Symfony\Component\Form\Test\FormBuilderInterface;

class EnumFieldConfigExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var EnumFieldConfigExtension */
    private $extension;

    /** @var EnumFieldConfigSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = $this->createMock(EnumFieldConfigSubscriber::class);

        $this->extension = new EnumFieldConfigExtension($this->subscriber);
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([ConfigType::class], EnumFieldConfigExtension::getExtendedTypes());
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber);

        $this->extension->buildForm($builder, []);
    }
}
