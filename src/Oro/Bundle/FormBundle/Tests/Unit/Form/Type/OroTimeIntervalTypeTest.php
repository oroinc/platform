<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\OroTimeIntervalType;

class OroTimeIntervalTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OroTimeIntervalType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new OroTimeIntervalType();
    }

    public function testGetName()
    {
        $this->assertEquals(OroTimeIntervalType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('time', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $expectedOptions = array(
            'widget'         => 'single_text',
            'with_seconds'   => true,
            'model_timezone' => 'UTC',
            'view_timezone'  => 'UTC',
        );

        $form = $this->factory->create($this->type);
        $form->submit(new \DateTime());

        $options = $form->getConfig()->getOptions();
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $options);
            $this->assertEquals($expectedValue, $options[$name]);
        }
    }

    public function testBuildView()
    {
        $form = $this->factory->create($this->type);
        $form->submit(new \DateTime());
        $view = $form->createView();

        $this->assertEquals('text', $view->vars['type']);
    }
}
