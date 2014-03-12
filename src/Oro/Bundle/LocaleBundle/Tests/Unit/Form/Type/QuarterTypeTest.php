<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\LocaleBundle\Form\Type\QuarterType;

class QuarterTypeTest extends FormIntegrationTestCase
{
    /** @var QuarterType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new QuarterType();
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_quarter', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('date', $this->formType->getParent());
    }

    public function testBuildForm()
    {
        $form    = $this->factory->create($this->formType);
        $options = $form->getConfig()->getOptions();

        $this->assertTrue(isset($options['format']));
        $this->assertTrue(isset($options['input']));
        $this->assertEquals('array', $options['input']);
        $this->assertEquals('dMMMy', $options['format']);
    }
}
