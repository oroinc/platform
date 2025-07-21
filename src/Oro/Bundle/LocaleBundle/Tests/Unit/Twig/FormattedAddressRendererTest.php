<?php

declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Twig\FormattedAddressRenderer;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment as TwigEnvironment;
use Twig\Template;
use Twig\TemplateWrapper;

final class FormattedAddressRendererTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private TwigEnvironment&MockObject $twigEnvironment;
    private FormattedAddressRenderer $renderer;

    #[\Override]
    protected function setUp(): void
    {
        $this->twigEnvironment = $this->createMock(TwigEnvironment::class);
        $this->addressFormatter = $this->createMock(AddressFormatter::class);

        $this->renderer = new FormattedAddressRenderer($this->twigEnvironment, true);

        $this->setUpLoggerMock($this->renderer);
    }

    /**
     * @dataProvider renderAddressDataProvider
     */
    public function testRenderAddressSuccessfully(
        array $addressParts,
        string $addressFormat,
        string $newLineSeparator,
        string $expectedResult
    ): void {
        $this->twigEnvironment->expects(self::any())
            ->method('mergeGlobals')
            ->willReturnArgument(0);

        $template = $this->createMock(Template::class);
        $this->twigEnvironment->expects(self::once())
            ->method('load')
            ->with('@OroLocale/Twig/address.html.twig')
            ->willReturn(new TemplateWrapper($this->twigEnvironment, $template));

        $template->expects(self::any())
            ->method('hasBlock')
            ->willReturnCallback(static function (string $blockName) {
                return $blockName === 'address_part' || $blockName === 'address_part_phone';
            });

        $template->expects(self::any())
            ->method('renderBlock')
            ->willReturnCallback(function (string $blockName, array $context) {
                return implode(
                    '_',
                    [$blockName, implode('_', [...array_keys($context), ...array_values($context)])]
                );
            });

        $result = $this->renderer->renderAddress($addressParts, $addressFormat, $newLineSeparator);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function renderAddressDataProvider(): array
    {
        return [
            'empty' => [
                'addressParts' => [],
                'addressFormat' => '',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_',
            ],
            'empty format' => [
                'addressParts' => ['%part1%' => 'value1'],
                'addressFormat' => '',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_',
            ],
            'empty parts' => [
                'addressParts' => [],
                'addressFormat' => '%part1%',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_%part1%',
            ],
            'missing part' => [
                'addressParts' => ['%part1%' => 'value1'],
                'addressFormat' => '%part1% %part2%',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_is_html_safe_part1_value1_ ' .
                    '%part2%',
            ],
            'empty part' => [
                'addressParts' => ['%part1%' => 'value1', '%part2%' => ''],
                'addressFormat' => '%part1% %part2%',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_is_html_safe_part1_value1_ ' .
                    'address_part_part_name_part_value_is_html_safe_part2__',
            ],
            'custom block' => [
                'addressParts' => ['%part1%' => 'value1', '%phone%' => '424242'],
                'addressFormat' => '%part1% %phone%',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_is_html_safe_part1_value1_ ' .
                    'address_part_phone_part_name_part_value_is_html_safe_phone_424242_',
            ],
            'new line' => [
                'addressParts' => ['%part1%' => 'value1', '%part2%' => 'value'],
                'addressFormat' => '%part1%\n%part2%',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_is_html_safe_part1_value1_' .
                    '<br />' . PHP_EOL . 'address_part_part_name_part_value_is_html_safe_part2_value_',
            ],
            'custom separator' => [
                'addressParts' => ['%part1%' => 'value1', '%part2%' => 'value'],
                'addressFormat' => '%part1%\n%part2%',
                'newLineSeparator' => ',',
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_is_html_safe_part1_value1_,' .
                    'address_part_part_name_part_value_is_html_safe_part2_value_',
            ],
            'multiple spaces' => [
                'addressParts' => ['%part1%' => 'value1', '%phone%' => '424242'],
                'addressFormat' => '  %part1%  %phone%  ',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_is_html_safe_part1_value1_ ' .
                    'address_part_phone_part_name_part_value_is_html_safe_phone_424242_',
            ],
            'multiple new lines' => [
                'addressParts' => ['%part1%' => 'value1', '%phone%' => '424242'],
                'addressFormat' => '\n%part1%\n\n %phone%\n',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_is_html_safe_part1_value1_' .
                    '<br />' . PHP_EOL . ' address_part_phone_part_name_part_value_is_html_safe_phone_424242_',
            ],
            'spaces followed by new line' => [
                'addressParts' => ['%part1%' => 'value1', '%phone%' => '424242'],
                'addressFormat' => ' \n  \n%part1%   \n\n %phone%\n',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_is_html_safe_part1_value1_' .
                    '<br />' . PHP_EOL . ' address_part_phone_part_name_part_value_is_html_safe_phone_424242_',
            ],
        ];
    }

    public function testRenderAddressHandlesException(): void
    {
        $addressParts = ['%country%' => 'US'];
        $addressFormat = "%country%";

        $exception = new \RuntimeException('Twig template not found');
        $this->twigEnvironment->expects(self::any())
            ->method('load')
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Rendering of an address failed: {message}',
                [
                    'message' => $exception->getMessage(),
                    'addressParts' => $addressParts,
                    'addressFormat' => $addressFormat,
                    'throwable' => $exception,
                ]
            );

        $result = $this->renderer->renderAddress($addressParts, $addressFormat);

        self::assertStringContainsString('Rendering of an address failed: Twig template not found', $result);
    }
}
