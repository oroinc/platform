<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\LocaleBundle\Form\Type\EnabledLocalizationSelectType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnabledLocalizationSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var EnabledLocalizationSelectType
     */
    private $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->formType = new EnabledLocalizationSelectType();
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $resolved = $resolver->resolve();

        self::assertEquals('oro_enabled_localization', $resolved['autocomplete_alias']);
        self::assertEquals(false, $resolved['create_enabled']);
        self::assertEquals('enabled-localizations-select-grid', $resolved['grid_name']);
        self::assertEquals(['component' => 'autocomplete-enabledlocalization'], $resolved['configs']);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale_enabled_localization', $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_locale_enabled_localization', $this->formType->getBlockPrefix());
    }
}
