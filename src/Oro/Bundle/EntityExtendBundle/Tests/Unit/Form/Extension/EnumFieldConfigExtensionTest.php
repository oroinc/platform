<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityExtendBundle\Form\EventListener\EnumFieldConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\Form\Extension\EnumFieldConfigExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Test\FormBuilderInterface;

class EnumFieldConfigExtensionTest extends TestCase
{
    private EnumFieldConfigExtension $extension;
    private EnumFieldConfigSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        $this->subscriber = $this->createMock(EnumFieldConfigSubscriber::class);

        $this->extension = new EnumFieldConfigExtension($this->subscriber);
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([ConfigType::class], EnumFieldConfigExtension::getExtendedTypes());
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber);

        $this->extension->buildForm($builder, []);
    }
}
