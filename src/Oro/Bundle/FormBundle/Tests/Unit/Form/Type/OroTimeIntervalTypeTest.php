<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroTimeIntervalType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroTimeIntervalTypeTest extends FormIntegrationTestCase
{
    /** @var OroTimeIntervalType */
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
        $this->assertEquals('text', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $expectedOptions = array(
            'tooltip' => 'oro.form.oro_time_interval.tooltip',
            'type' => 'text',
            'input_property_path' => null,
        );

        $form = $this->factory->create($this->type);

        $options = $form->getConfig()->getOptions();
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $options);
            $this->assertEquals($expectedValue, $options[$name]);
        }
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param $value
     * @param $expected
     */
    public function testSubmit($value, $expected)
    {
        $form = $this->factory->create($this->type);
        $form->submit($value);
        $data = $form->getData();

        $this->assertInstanceOf($expected, $data);
    }

    public function submitDataProvider()
    {
        return [
            'default' => [
                120,
                'DateTime',
            ],
            'BC datetime should pass trough' => [
                new \DateTime(),
                'DateTime',
            ],
        ];
    }

    public function testConfigureOptions()
    {
        $expectedOptions = array(
            'tooltip' => 'test',
            'input_property_path' => 'durationString',
        );

        $form = $this->factory->create($this->type, null, $expectedOptions);

        $options = $form->getConfig()->getOptions();
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $options);
            $this->assertEquals($expectedValue, $options[$name]);
        }
    }
}
