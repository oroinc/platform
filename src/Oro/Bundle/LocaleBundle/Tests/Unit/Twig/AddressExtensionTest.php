<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Twig\AddressExtension;
use Oro\Bundle\LocaleBundle\Twig\FormattedAddressRenderer;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddressExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private AddressFormatter&MockObject $formatter;

    private FormattedAddressRenderer&MockObject $formattedAddressRenderer;

    private AddressExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->formatter = $this->createMock(AddressFormatter::class);
        $this->formattedAddressRenderer = $this->createMock(FormattedAddressRenderer::class);

        $container = self::getContainerBuilder()
            ->add('oro_locale.formatter.address', $this->formatter)
            ->add('oro_locale.twig.formatted_address_renderer', $this->formattedAddressRenderer)
            ->getContainer($this);

        $this->extension = new AddressExtension($container);
    }

    public function testFormatAddress(): void
    {
        $address = $this->createMock(AddressInterface::class);
        $country = 'CA';
        $newLineSeparator = '<br/>';
        $expectedResult = 'expected result';

        $this->formatter->expects(self::once())
            ->method('format')
            ->with($address, $country, $newLineSeparator)
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_address', [$address, $country, $newLineSeparator])
        );
    }

    public function testFormatAddressHtmlWithCountry(): void
    {
        $addressParts = ['%part1%' => 'value1', '%part2%' => 'value2'];
        $addressFormat = '%part1%\n%part2%';
        $newLineSeparator = "\n";
        $country = 'US';
        $address = $this->createMock(AddressInterface::class);

        $this->formatter->expects(self::never())
            ->method('getCountry');

        $this->formatter->expects(self::once())
            ->method('getAddressFormat')
            ->with($country)
            ->willReturn($addressFormat);

        $this->formatter->expects(self::once())
            ->method('getAddressParts')
            ->with($address, $addressFormat, $country)
            ->willReturn($addressParts);

        $expectedResult = 'rendered address';
        $this->formattedAddressRenderer
            ->expects(self::once())
            ->method('renderAddress')
            ->with($addressParts, $addressFormat, $newLineSeparator)
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFilter(
                $this->extension,
                'oro_format_address_html',
                [$address, $country, $newLineSeparator]
            )
        );
    }

    public function testFormatAddressHtmlWithoutCountry(): void
    {
        $addressParts = ['%part1%' => 'value1', '%part2%' => 'value2'];
        $addressFormat = '%part1%\n%part2%';
        $newLineSeparator = "\n";
        $country = 'US';
        $address = $this->createMock(AddressInterface::class);

        $this->formatter->expects(self::once())
            ->method('getCountry')
            ->willReturn($country);

        $this->formatter->expects(self::once())
            ->method('getAddressFormat')
            ->with($country)
            ->willReturn($addressFormat);

        $this->formatter->expects(self::once())
            ->method('getAddressParts')
            ->with($address, $addressFormat, $country)
            ->willReturn($addressParts);

        $expectedResult = 'rendered address';
        $this->formattedAddressRenderer
            ->expects(self::once())
            ->method('renderAddress')
            ->with($addressParts, $addressFormat, $newLineSeparator)
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFilter(
                $this->extension,
                'oro_format_address_html',
                [$address, null, $newLineSeparator]
            )
        );
    }
}
