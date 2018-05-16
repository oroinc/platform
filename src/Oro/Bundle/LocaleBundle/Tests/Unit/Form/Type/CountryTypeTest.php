<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\CountryType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\CountryType as BaseCountryType;

class CountryTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CountryType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->formType = new CountryType();
    }

    public function testConfigureOptions()
    {
        $choices = $this->factory->create(CountryType::class)
            ->createView()->vars['choices'];

        $this->assertContains(new ChoiceView('DE', 'DE', 'Germany'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('GB', 'GB', 'United Kingdom'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('US', 'US', 'United States'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('FR', 'FR', 'France'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('MY', 'MY', 'Malaysia'), $choices, '', false, false);
    }

    public function testGetParent()
    {
        $this->assertEquals(BaseCountryType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale_country', $this->formType->getName());
    }
}
