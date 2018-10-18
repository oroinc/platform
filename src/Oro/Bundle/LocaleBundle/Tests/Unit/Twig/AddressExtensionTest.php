<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Twig\AddressExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class AddressExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var AddressExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $formatter;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder(AddressFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_locale.formatter.address', $this->formatter)
            ->getContainer($this);

        $this->extension = new AddressExtension($container);
    }

    public function testFormatAddress()
    {
        $address = $this->createMock('Oro\Bundle\LocaleBundle\Model\AddressInterface');
        $country = 'CA';
        $newLineSeparator = '<br/>';
        $expectedResult = 'expected result';

        $this->formatter->expects($this->once())->method('format')
            ->with($address, $country, $newLineSeparator)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_address', [$address, $country, $newLineSeparator])
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale_address', $this->extension->getName());
    }
}
