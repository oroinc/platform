<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\NumberTypeExtension;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class NumberTypeExtensionTest extends FormIntegrationTestCase
{
    private const float UNFORMATTED_VALUE = 1234.123456789;
    private const string FORMATTED_VALUE = '1,234.123456789';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(NumberType::class, self::UNFORMATTED_VALUE, ['grouping' => true]);
        $view = $form->createView();

        self::assertArrayHasKey('value', $view->vars);
        self::assertEquals(self::FORMATTED_VALUE, $view->vars['value']);

        self::assertArrayHasKey('attr', $view->vars);
        self::assertArrayHasKey('data-limit-decimals', $view->vars['attr']);
        self::assertEquals(self::FORMATTED_VALUE, $view->vars['value']);
        self::assertEquals(1, $view->vars['attr']['data-limit-decimals']);

        self::assertTrue($form->getConfig()->getOption('limit_decimals'));
    }

    #[\Override]
    protected function getTypeExtensions(): array
    {
        $numberFormatter = $this->createMock(NumberFormatter::class);
        $numberFormatter->expects(self::any())
            ->method('formatDecimal')
            ->with(self::UNFORMATTED_VALUE, [\NumberFormatter::GROUPING_USED => true])
            ->willReturn(self::FORMATTED_VALUE);

        return array_merge(
            parent::getTypeExtensions(),
            [
                new NumberTypeExtension($numberFormatter),
            ]
        );
    }
}
