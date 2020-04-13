<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityExtendBundle\Form\Extension\DynamicFieldsOptionsExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DynamicFieldsOptionsExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigureOptions(): void
    {
        $optionsResolver = new OptionsResolver();
        $extension = new DynamicFieldsOptionsExtension();
        $extension->configureOptions($optionsResolver);

        $this->assertEquals(
            [
                'dynamic_fields_ignore_exception' => false,
                'is_dynamic_field' => false
            ],
            $optionsResolver->resolve()
        );
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([FormType::class], DynamicFieldsOptionsExtension::getExtendedTypes());
    }
}
