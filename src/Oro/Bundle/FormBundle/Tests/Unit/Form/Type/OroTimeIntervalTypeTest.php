<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroTimeIntervalType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroTimeIntervalTypeTest extends FormIntegrationTestCase
{
    public function testGetName()
    {
        $type = new OroTimeIntervalType();
        $this->assertEquals(OroTimeIntervalType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = new OroTimeIntervalType();
        $this->assertEquals(TimeType::class, $type->getParent());
    }

    public function testConfigureOptions()
    {
        $expectedOptions = array(
            'widget'         => 'single_text',
            'with_minutes'   => true,
            'with_seconds'   => true,
            'model_timezone' => 'UTC',
            'view_timezone'  => 'UTC',
        );

        $form = $this->factory->create(OroTimeIntervalType::class);

        $options = $form->getConfig()->getOptions();
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $options);
            $this->assertEquals($expectedValue, $options[$name]);
        }
    }

    public function testBuildView()
    {
        $form = $this->factory->create(OroTimeIntervalType::class);
        $view = $form->createView();

        $this->assertEquals('text', $view->vars['type']);
    }
}
