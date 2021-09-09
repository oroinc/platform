<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\DateTimeExtension;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;

class OroDateTimeTypeTest extends FormIntegrationTestCase
{
    public function testGetParent(): void
    {
        $type = new OroDateTimeType();
        self::assertEquals(DateTimeType::class, $type->getParent());
    }

    public function testGetName(): void
    {
        $type = new OroDateTimeType();
        self::assertEquals('oro_datetime', $type->getName());
    }

    public function testConfigureOptions(): void
    {
        $expectedOptions = [
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'format' => DateTimeExtension::HTML5_FORMAT_WITH_TIMEZONE,
            'widget' => 'single_text',
            'placeholder' => 'oro.form.click_here_to_select',
            'years' => [],
            'html5' => false,
        ];

        $form = $this->factory->create(OroDateTimeType::class);
        $form->submit((new \DateTime()));

        $options = $form->getConfig()->getOptions();
        foreach ($expectedOptions as $name => $expectedValue) {
            self::assertArrayHasKey($name, $options);
            self::assertEquals($expectedValue, $options[$name]);
        }
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expectedKeys
     * @param array $expectedValues
     */
    public function testFinishView(array $options, array $expectedKeys, array $expectedValues): void
    {
        $form = $this->createMock(Form::class);

        $view = new FormView();
        $type = new OroDateTimeType();
        $type->finishView($view, $form, $options);
        foreach ($expectedKeys as $key => $expectedKey) {
            self::assertArrayHasKey($expectedKey, $view->vars);
            self::assertEquals($expectedValues[$key], $view->vars[$expectedKey]);
        }
    }

    public function optionsDataProvider(): array
    {
        return [
            [
                ['placeholder' => 'some.placeholder', 'minDate' => '-120y', 'maxDate' => '0'],
                ['attr', 'minDate', 'maxDate'],
                [
                    ['placeholder' => 'some.placeholder'],
                    '-120y',
                    '0',
                ],
            ],
            [
                ['years' => [2001, 2002, 2003], 'minDate' => '-120y', 'maxDate' => '0'],
                ['years', 'minDate', 'maxDate'],
                ['2001:2003', '-120y', '0'],
            ],
        ];
    }

    /**
     * @dataProvider valuesDataProvider
     * @param string $value
     * @param \DateTime $expectedValue
     */
    public function testSubmitValidData(string $value, \DateTime $expectedValue): void
    {
        $form = $this->factory->create(OroDateTimeType::class);
        $form->submit($value);
        self::assertEquals($expectedValue->format('U'), $form->getData()->format('U'));
    }

    public function valuesDataProvider(): array
    {
        return [
            [
                '2002-10-02T15:00:00+00:00',
                new \DateTime('2002-10-02T15:00:00+00:00'),
            ],
            [
                '2002-10-02T15:00:00Z',
                new \DateTime('2002-10-02T15:00:00Z'),
            ],
        ];
    }
}
