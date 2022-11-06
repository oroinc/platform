<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Twig\AddressExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper;

class AddressExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var AddressFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var AddressExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(AddressFormatter::class);

        $container = self::getContainerBuilder()
            ->add('oro_locale.formatter.address', $this->formatter)
            ->getContainer($this);

        $this->extension = new AddressExtension($container);
    }

    public function testFormatAddress()
    {
        $address = $this->createMock(AddressInterface::class);
        $country = 'CA';
        $newLineSeparator = '<br/>';
        $expectedResult = 'expected result';

        $this->formatter->expects($this->once())
            ->method('format')
            ->with($address, $country, $newLineSeparator)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_address', [$address, $country, $newLineSeparator])
        );
    }

    /**
     * @dataProvider formatAddressHtmlDataProvider
     */
    public function testFormatAddressHtml(
        array $addressParts,
        string $addressFormat,
        ?string $country,
        string $newLineSeparator,
        string $expectedResult
    ): void {
        $address = $this->createMock(AddressInterface::class);

        $this->formatter->expects($this->any())
            ->method('getCountry')
            ->willReturn($country);

        $this->formatter->expects($this->once())
            ->method('getAddressFormat')
            ->with($country)
            ->willReturn($addressFormat);

        $this->formatter->expects($this->once())
            ->method('getAddressParts')
            ->with($address, $addressFormat, $country)
            ->willReturn($addressParts);

        $environment = $this->createMock(Environment::class);
        $template = $this->createMock(Template::class);
        $environment->expects($this->once())
            ->method('load')
            ->with('@OroLocale/Twig/address.html.twig')
            ->willReturn(new TemplateWrapper($environment, $template));

        $environment->expects(self::any())
            ->method('mergeGlobals')
            ->willReturnCallback(function ($context) {
                return $context;
            });

        $template->expects($this->any())
            ->method('hasBlock')
            ->willReturnCallback(function (string $blockName) {
                return $blockName === 'address_part' || $blockName === 'address_part_phone';
            });

        $template->expects(self::any())
            ->method('displayBlock')
            ->willReturnCallback(function (string $blockName, array $context) {
                echo  implode(
                    '_',
                    [$blockName, implode('_', [...array_keys($context), ...array_values($context)])]
                );
            });

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter(
                $this->extension,
                'oro_format_address_html',
                [$environment, $address, $country, $newLineSeparator]
            )
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function formatAddressHtmlDataProvider(): array
    {
        return [
            'empty' => [
                'addressParts' => [],
                'addressFormat' => '',
                'country' => 'US',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_',
            ],
            'empty format' => [
                'addressParts' => ['%part1%' => 'value1'],
                'addressFormat' => '',
                'country' => 'US',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_',
            ],
            'empty parts' => [
                'addressParts' => [],
                'addressFormat' => '%part1%',
                'country' => 'US',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_%part1%',
            ],
            'empty country' => [
                'addressParts' => ['%part1%' => 'value1'],
                'addressFormat' => '%part1%',
                'country' => null,
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_part1_value1',
            ],
            'missing part' => [
                'addressParts' => ['%part1%' => 'value1'],
                'addressFormat' => '%part1% %part2%',
                'country' => 'US',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_part1_value1 %part2%',
            ],
            'empty part' => [
                'addressParts' => ['%part1%' => 'value1', '%part2%' => ''],
                'addressFormat' => '%part1% %part2%',
                'country' => 'US',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_part1_value1 '.
                    'address_part_part_name_part_value_part2_',
            ],
            'custom block' => [
                'addressParts' => ['%part1%' => 'value1', '%phone%' => '424242'],
                'addressFormat' => '%part1% %phone%',
                'country' => 'US',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_part1_value1 ' .
                    'address_part_phone_part_name_part_value_phone_424242',
            ],
            'new line' => [
                'addressParts' => ['%part1%' => 'value1', '%part2%' => 'value'],
                'addressFormat' => '%part1%\n%part2%',
                'country' => 'US',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_part1_value1<br />' . PHP_EOL .
                    'address_part_part_name_part_value_part2_value',
            ],
            'custom separator' => [
                'addressParts' => ['%part1%' => 'value1', '%part2%' => 'value'],
                'addressFormat' => '%part1%\n%part2%',
                'country' => 'US',
                'newLineSeparator' => ',',
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_part1_value1,' .
                    'address_part_part_name_part_value_part2_value',
            ],
            'multiple spaces' => [
                'addressParts' => ['%part1%' => 'value1', '%phone%' => '424242'],
                'addressFormat' => '  %part1%  %phone%  ',
                'country' => 'US',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_part1_value1 ' .
                    'address_part_phone_part_name_part_value_phone_424242',
            ],
            'multiple new lines' => [
                'addressParts' => ['%part1%' => 'value1', '%phone%' => '424242'],
                'addressFormat' => '\n%part1%\n\n %phone%\n',
                'country' => 'US',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_part1_value1<br />' . PHP_EOL .
                    ' address_part_phone_part_name_part_value_phone_424242',
            ],
            'spaces followed by new line' => [
                'addressParts' => ['%part1%' => 'value1', '%phone%' => '424242'],
                'addressFormat' => ' \n  \n%part1%   \n\n %phone%\n',
                'country' => 'US',
                'newLineSeparator' => PHP_EOL,
                'expectedResult' => 'address_formatted_address_part_part_name_part_value_part1_value1<br />' . PHP_EOL .
                    ' address_part_phone_part_name_part_value_phone_424242',
            ],
        ];
    }
}
