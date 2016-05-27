<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroDurationType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroDurationTypeTest extends FormIntegrationTestCase
{
    /** @var OroDurationType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new OroDurationType();
    }

    public function testGetName()
    {
        $this->assertEquals(OroDurationType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('text', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $expectedOptions = [
            'tooltip' => 'oro.form.oro_duration.tooltip',
            'type' => 'text',
        ];

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

        $this->assertEquals($expected, $data);
    }

    public function submitDataProvider()
    {
        return [
            'default' => [
                '1:30', // 1 min 30 sec
                90,
            ],
            'BC datetime should pass trough' => [
                '1h 30m',
                5400,
            ],
            'invalid' => [
                'test',
                0,
            ],
        ];
    }

    public function testSubmitInvalidData()
    {
        $form = $this->factory->create($this->type);
        $form->submit('invalid');
        $errors = $form->getErrors();

        $this->assertCount(1, $errors);
    }

    public function testConfigureOptions()
    {
        $expectedOptions = [
            'tooltip' => 'test',
        ];

        $form = $this->factory->create($this->type, null, $expectedOptions);

        $options = $form->getConfig()->getOptions();
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $options);
            $this->assertEquals($expectedValue, $options[$name]);
        }
    }
}
