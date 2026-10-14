<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Formatter;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Form\Type\ConfigCheckbox;
use Oro\Bundle\ConfigBundle\Formatter\SystemConfigValueFormatter;
use Oro\Bundle\FormBundle\Form\Type\OroTextListType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SystemConfigValueFormatterTest extends TestCase
{
    private ConfigBag&MockObject $configBag;
    private TranslatorInterface&MockObject $translator;
    private SystemConfigValueFormatter $formatter;

    #[\Override]
    protected function setUp(): void
    {
        $this->configBag = $this->createMock(ConfigBag::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new SystemConfigValueFormatter($this->configBag, $this->translator);
    }

    /**
     * @dataProvider formatReturnsYesOrNoForBooleanSettingProvider
     */
    public function testFormatReturnsYesOrNoForBooleanSetting(
        bool|string $value,
        string $expectedTranslationKey,
        string $expectedText
    ): void {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn([
                'data_type' => 'boolean',
                'type' => ConfigCheckbox::class,
                'options' => [
                    'label' => 'oro.shoppinglist.system_configuration.fields.availability_for_guests.label',
                    'tooltip' => 'oro.shoppinglist.system_configuration.fields.availability_for_guests.tooltip',
                    'required' => false,
                ],
            ]);
        $this->translator->expects(self::once())
            ->method('trans')
            ->with($expectedTranslationKey)
            ->willReturn($expectedText);

        self::assertSame(
            $expectedText,
            $this->formatter->format('oro_shopping_list.availability_for_guests', $value)
        );
    }

    public static function formatReturnsYesOrNoForBooleanSettingProvider(): iterable
    {
        yield 'true' => [true, 'Yes', 'Yes'];
        yield 'false' => [false, 'No', 'No'];
        yield 'string one' => ['1', 'Yes', 'Yes'];
        yield 'string zero' => ['0', 'No', 'No'];
    }

    /**
     * @dataProvider formatReturnsTranslatedLabelOfMatchingChoiceProvider
     */
    public function testFormatReturnsTranslatedLabelOfMatchingChoice(
        string $key,
        array $definition,
        int|string $value,
        string $expectedTranslationKey,
        string $expectedText
    ): void {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with($key)
            ->willReturn($definition);
        $this->translator->expects(self::once())
            ->method('trans')
            ->with($expectedTranslationKey)
            ->willReturn($expectedText);

        self::assertSame($expectedText, $this->formatter->format($key, $value));
    }

    public static function formatReturnsTranslatedLabelOfMatchingChoiceProvider(): iterable
    {
        yield 'string choice' => [
            'oro_currency.currency_display',
            [
                'data_type' => 'string',
                'type' => ChoiceType::class,
                'search_type' => 'choice',
                'options' => [
                    'label' => 'oro.currency.system_configuration.fields.currency_display.label',
                    'tooltip' => 'oro.currency.form.tooltip.currency_display',
                    'required' => true,
                    'choices' => [
                        'oro.currency.system_configuration.fields.currency_display.choices.iso_code' => 'iso_code',
                        'oro.currency.system_configuration.fields.currency_display.choices.symbol' => 'symbol',
                    ],
                ],
            ],
            'symbol',
            'oro.currency.system_configuration.fields.currency_display.choices.symbol',
            'Currency Symbol',
        ];
        yield 'integer choice' => [
            'oro_attachment.png_quality',
            [
                'data_type' => 'integer',
                'type' => ChoiceType::class,
                'search_type' => 'choice',
                'options' => [
                    'label' => 'oro.attachment.config.png_quality',
                    'tooltip' => 'oro.attachment.config.quality_tooltip',
                    'required' => true,
                    'choices' => [
                        'oro.attachment.config.png_preserve_quality' => 100,
                        'oro.attachment.config.png_minimize_file_size' => 35,
                    ],
                ],
            ],
            35,
            'oro.attachment.config.png_minimize_file_size',
            'Minimize file size',
        ];
    }

    public function testFormatReturnsValueAsTextWhenNoChoiceMatches(): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_currency.currency_display')
            ->willReturn([
                'data_type' => 'string',
                'type' => ChoiceType::class,
                'search_type' => 'choice',
                'options' => [
                    'label' => 'oro.currency.system_configuration.fields.currency_display.label',
                    'tooltip' => 'oro.currency.form.tooltip.currency_display',
                    'required' => true,
                    'choices' => [
                        'oro.currency.system_configuration.fields.currency_display.choices.iso_code' => 'iso_code',
                        'oro.currency.system_configuration.fields.currency_display.choices.symbol' => 'symbol',
                    ],
                ],
            ]);
        $this->translator->expects(self::never())
            ->method('trans');

        self::assertSame('full_name', $this->formatter->format('oro_currency.currency_display', 'full_name'));
    }

    /**
     * @dataProvider formatReturnsIntegerValueAsTextProvider
     */
    public function testFormatReturnsIntegerValueAsText(int $value, string $expectedText): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn([
                'data_type' => 'integer',
                'type' => IntegerType::class,
                'search_type' => 'text',
                'options' => [
                    'label' => 'oro.shoppinglist.system_configuration.groups.shopping_list_limit.title',
                    'tooltip' => 'oro.shoppinglist.system_configuration.fields.shopping_list_limit.tooltip',
                    'required' => true,
                ],
            ]);
        $this->translator->expects(self::never())
            ->method('trans');

        self::assertSame($expectedText, $this->formatter->format('oro_shopping_list.shopping_list_limit', $value));
    }

    public static function formatReturnsIntegerValueAsTextProvider(): iterable
    {
        yield 'positive' => [5, '5'];
        yield 'zero' => [0, '0'];
    }

    public function testFormatReturnsStringValueAsText(): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_ui.application_url')
            ->willReturn([
                'data_type' => 'string',
                'type' => UrlType::class,
                'search_type' => 'text',
                'options' => [
                    'default_protocol' => 'http',
                    'label' => 'oro.ui.system_configuration.fields.application_url.label',
                    'resettable' => false,
                    'required' => true,
                ],
            ]);
        $this->translator->expects(self::never())
            ->method('trans');

        self::assertSame(
            'https://www.example.com',
            $this->formatter->format('oro_ui.application_url', 'https://www.example.com')
        );
    }

    public function testFormatJoinsFormattedItemsOfArrayValue(): void
    {
        $this->configBag->expects(self::exactly(3))
            ->method('getFieldsRoot')
            ->with('oro_google_integration.sso_domains')
            ->willReturn([
                'data_type' => 'array',
                'type' => OroTextListType::class,
                'search_type' => 'text',
                'options' => [
                    'label' => 'oro.google_integration.system_configuration.fields.sso_domains.label',
                    'tooltip' => 'oro.google_integration.system_configuration.fields.sso_domains.tooltip',
                    'required' => false,
                    'resettable' => false,
                ],
            ]);
        $this->translator->expects(self::never())
            ->method('trans');

        self::assertSame(
            'example.com, example.org',
            $this->formatter->format('oro_google_integration.sso_domains', ['example.com', 'example.org'])
        );
    }

    /**
     * @dataProvider formatReturnsNotAvailableForEmptyOrUnsupportedValueProvider
     */
    public function testFormatReturnsNotAvailableForEmptyOrUnsupportedValue(mixed $value): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn([
                'data_type' => 'boolean',
                'type' => ConfigCheckbox::class,
                'options' => [
                    'label' => 'oro.shoppinglist.system_configuration.fields.availability_for_guests.label',
                    'tooltip' => 'oro.shoppinglist.system_configuration.fields.availability_for_guests.tooltip',
                    'required' => false,
                ],
            ]);
        $this->translator->expects(self::once())
            ->method('trans')
            ->with('N/A')
            ->willReturn('N/A');

        self::assertSame('N/A', $this->formatter->format('oro_shopping_list.availability_for_guests', $value));
    }

    public static function formatReturnsNotAvailableForEmptyOrUnsupportedValueProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'empty array' => [[]];
        yield 'object' => [new \stdClass()];
    }

    public function testFormatReturnsNotAvailableForUnknownSetting(): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_unknown.setting')
            ->willReturn(false);
        $this->translator->expects(self::once())
            ->method('trans')
            ->with('N/A')
            ->willReturn('N/A');

        self::assertSame('N/A', $this->formatter->format('oro_unknown.setting', true));
    }
}
