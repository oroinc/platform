<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroDurationType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroDurationTypeTest extends FormIntegrationTestCase
{
    public function testGetName()
    {
        $type = new OroDurationType();
        $this->assertEquals(OroDurationType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = new OroDurationType();
        $this->assertEquals(TextType::class, $type->getParent());
    }

    public function testConfigureOptions()
    {
        $expectedOptions = [
            'tooltip' => 'oro.form.oro_duration.tooltip',
        ];

        $form = $this->factory->create(OroDurationType::class);

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
        $form = $this->factory->create(OroDurationType::class);
        $form->submit($value);
        $data = $form->getData();

        $this->assertEquals($expected, $data);
    }

    /**
     * @return array
     */
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

    public function testSubmitInvalidDataThrowsError()
    {
        $form = $this->factory->create(OroDurationType::class);
        $form->submit('invalid');
        $errors = $form->getErrors();

        $this->assertCount(1, $errors);
    }
}
