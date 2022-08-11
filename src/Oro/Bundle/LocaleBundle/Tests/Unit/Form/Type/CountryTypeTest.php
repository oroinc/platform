<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\CountryType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\CountryType as BaseCountryType;
use Symfony\Component\Intl\Countries;

class CountryTypeTest extends FormIntegrationTestCase
{
    /** @var CountryType */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formType = new CountryType();
    }

    public function testConfigureOptions()
    {
        /** @var \Symfony\Component\Form\ChoiceList\View\ChoiceView[] $choices */
        $choices = $this->factory->create(CountryType::class)
            ->createView()->vars['choices'];
        $values = [];
        foreach ($choices as $choice) {
            $values[$choice->value] = $choice->label;
        }
        $this->assertEquals(Countries::getNames('en'), $values);
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
