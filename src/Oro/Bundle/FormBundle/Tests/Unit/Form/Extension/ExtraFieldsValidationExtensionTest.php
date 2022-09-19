<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\ExtraFieldsValidationExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExtraFieldsValidationExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtraFieldsValidationExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new ExtraFieldsValidationExtension();
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        $this->assertTrue($resolver->hasDefault('extra_fields_message'));
        $resolvedOptions = $resolver->resolve();

        $this->assertSame('oro.form.extra_fields', $resolvedOptions['extra_fields_message']);
    }
}
