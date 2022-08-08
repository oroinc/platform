<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocaleType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\LocaleType as BaseLocaleType;
use Symfony\Component\Intl\Locales;

class LocaleTypeTest extends FormIntegrationTestCase
{
    /** @var LocaleType */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formType = new LocaleType();
    }

    public function testChoices()
    {
        $view = $this->factory->create(LocaleType::class)->createView();
        $choices = $view->vars['choices'];
        $values = [];
        foreach ($choices as $choice) {
            $values[$choice->value] = $choice->label;
        }
        $this->assertEquals(Locales::getNames('en'), $values);
    }

    public function testGetParent()
    {
        $this->assertEquals(BaseLocaleType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale', $this->formType->getName());
    }
}
