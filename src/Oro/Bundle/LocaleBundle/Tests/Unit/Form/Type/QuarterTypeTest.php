<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\QuarterType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class QuarterTypeTest extends FormIntegrationTestCase
{
    public function testGetName()
    {
        $formType = new QuarterType();
        $this->assertEquals('oro_quarter', $formType->getName());
    }

    public function testGetParent()
    {
        $formType = new QuarterType();
        $this->assertEquals(DateType::class, $formType->getParent());
    }

    public function testBuildForm()
    {
        $form    = $this->factory->create(QuarterType::class);
        $options = $form->getConfig()->getOptions();

        $this->assertTrue(isset($options['format']));
        $this->assertTrue(isset($options['input']));
        $this->assertEquals('array', $options['input']);
        $this->assertEquals('dMMMy', $options['format']);

        $this->assertFalse($form->has('year'));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "input" with value "timestamp" is invalid. Accepted values are: "array".
     */
    public function testBuildFormTryingToChangeInputType()
    {
        $this->factory->create(QuarterType::class, null, ['input' => 'timestamp']);
    }
}
