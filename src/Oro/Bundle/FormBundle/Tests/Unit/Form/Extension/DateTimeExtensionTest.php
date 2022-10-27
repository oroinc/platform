<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\DateTimeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider configureOptionsDataProvider
     *
     * @param array $options
     * @param array $expected
     */
    public function testConfigureOptions(array $options, array $expected): void
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefault('html5', true);
        (new DateTimeExtension())->configureOptions($optionsResolver);

        self::assertEquals($expected, $optionsResolver->resolve($options));
    }

    public function configureOptionsDataProvider(): array
    {
        return [
            'empty options' => [
                'options' => [],
                'expected' => [
                    'format' => DateTimeExtension::HTML5_FORMAT_WITH_TIMEZONE,
                    'html5' => false,
                ],
            ],
            'with timezone' => [
                'options' => ['format' => DateTimeExtension::HTML5_FORMAT_WITH_TIMEZONE],
                'expected' => [
                    'format' => DateTimeExtension::HTML5_FORMAT_WITH_TIMEZONE,
                    'html5' => false,
                ],
            ],
            'without timeout' => [
                'options' => ['format' => DateTimeExtension::HTML5_FORMAT_WITHOUT_TIMEZONE],
                'expected' => [
                    'format' => DateTimeExtension::HTML5_FORMAT_WITHOUT_TIMEZONE,
                    'html5' => true,
                ],
            ],
        ];
    }

    /**
     * @dataProvider buildViewDataProvider
     *
     * @param array $vars
     * @param array $options
     * @param string $expectedType
     */
    public function testBuildView(array $vars, array $options, string $expectedType): void
    {
        $view = new FormView();
        $view->vars = $vars;

        (new DateTimeExtension())->buildView($view, $this->createMock(FormInterface::class), $options);

        self::assertEquals($expectedType, $view->vars['type'] ?? '');
    }

    public function buildViewDataProvider(): array
    {
        return [
            'datetime-local type' => [
                'vars' => ['type' => 'datetime-local'],
                'options' => ['html5' => true, 'widget' => 'another_widget'],
                'expectedType' => 'datetime',
            ],
            'another type' => [
                'vars' => ['type' => 'another-type'],
                'options' => ['html5' => true, 'widget' => 'another_widget'],
                'expectedType' => 'another-type',
            ],
            'html5 and another widget' => [
                'vars' => [],
                'options' => ['html5' => true, 'widget' => 'another_widget'],
                'expectedType' => '',
            ],
            'html5' => [
                'vars' => [],
                'options' => ['html5' => true, 'format' => 'another format', 'widget' => 'single_text'],
                'expectedType' => 'datetime',
            ],
            'not html5 and format with timezone' => [
                'vars' => [],
                'options' => [
                    'html5' => false,
                    'format' => DateTimeExtension::HTML5_FORMAT_WITH_TIMEZONE,
                    'widget' => 'single_text',
                ],
                'expectedType' => 'datetime',
            ],
            'html5 and format with timezone' => [
                'vars' => [],
                'options' => [
                    'html5' => true,
                    'format' => DateTimeExtension::HTML5_FORMAT_WITH_TIMEZONE,
                    'widget' => 'single_text',
                ],
                'expectedType' => 'datetime',
            ],
        ];
    }
}
