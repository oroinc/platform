<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\ThemeBundle\Form\Type\ThemeConfigurationSelectType;
use Oro\Bundle\ThemeBundle\Form\Type\ThemeConfigurationType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ThemeConfigurationSelectTypeTest extends FormIntegrationTestCase
{
    protected ?ThemeConfigurationSelectType $type;

    protected function setUp(): void
    {
        $this->type = new ThemeConfigurationSelectType();
    }

    public function testGetParent(): void
    {
        self::assertEquals(OroEntitySelectOrCreateInlineType::class, $this->type->getParent());
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();

        $this->type->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve();

        self::assertArrayHasKey('autocomplete_alias', $resolvedOptions);
        self::assertArrayHasKey('configs', $resolvedOptions);
        self::assertEquals(ThemeConfigurationType::class, $resolvedOptions['autocomplete_alias']);
        self::assertEquals(
            ['placeholder' => 'oro.theme.themeconfiguration.form.choose'],
            $resolvedOptions['configs']
        );
    }
}
