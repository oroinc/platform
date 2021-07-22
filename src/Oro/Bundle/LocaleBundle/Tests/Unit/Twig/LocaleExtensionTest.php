<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Twig\LocaleExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class LocaleExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private const TEST_TYPE = 'test_format_type';
    private const TEST_FORMAT = 'MMM, d y t';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var LocaleExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->localeSettings =$this->createMock(LocaleSettings::class);

        $container = self::getContainerBuilder()
            ->add(LocaleSettings::class, $this->localeSettings)
            ->getContainer($this);

        $this->extension = new LocaleExtension($container);
    }

    public function testGetTimeZoneOffset(): void
    {
        $timezoneString = 'UTC';
        $timezoneOffset = '+00:00';

        $this->localeSettings->expects(self::once())
            ->method('getTimeZone')
            ->willReturn($timezoneString);

        self::assertEquals(
            $timezoneOffset,
            self::callTwigFunction($this->extension, 'oro_timezone_offset', [])
        );
    }

    public function testIsRtlMode(): void
    {
        $this->localeSettings->expects(self::any())
            ->method('isRtlMode')
            ->willReturn(true);

        self::assertTrue(self::callTwigFunction($this->extension, 'oro_is_rtl_mode', []));
    }
}
